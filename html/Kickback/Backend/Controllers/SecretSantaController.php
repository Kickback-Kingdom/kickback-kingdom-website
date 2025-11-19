<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use DateTime;
use DateTimeZone;
use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Models\Response;
use Kickback\Services\Database;
use Kickback\Services\Session;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Kickback\Backend\Models\RecordId;

class SecretSantaController
{
    public static function requireLogin(): Response
    {
        $account = null;
        if (!Session::readCurrentAccountInto($account) || is_null($account)) {
            return new Response(false, "You must be logged in to manage Secret Santa events.", null);
        }

        return new Response(true, "Authenticated", $account);
    }

    public static function createEvent(string $name, ?string $description, string $signupDeadline, string $giftDeadline): Response
    {
        $authResp = self::requireLogin();
        if (!$authResp->success) {
            return $authResp;
        }
        $account = $authResp->data;

        $signupDt = DateTime::createFromFormat('Y-m-d H:i:s', $signupDeadline);
        $giftDt = DateTime::createFromFormat('Y-m-d H:i:s', $giftDeadline);

        if (!$signupDt || !$giftDt) {
            return new Response(false, 'Invalid deadline format. Use Y-m-d H:i:s', null);
        }

        if ($giftDt <= $signupDt) {
            return new Response(false, 'Gift deadline must be after signup deadline.', null);
        }

        $newRecordId = new RecordId();
        $ctime = $newRecordId->ctime;
        $crand = $newRecordId->crand; 
        $inviteToken = self::generateInviteToken();

        $conn = self::getConnection();

        $stmt = $conn->prepare(
            "INSERT INTO secret_santa_events (ctime, crand, owner_id, name, description, invite_token, signup_deadline, gift_deadline) " .
            "VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if ($stmt === false) {
            return new Response(false, 'Failed to create event.', null);
        }

        $stmt->bind_param(
            'siisssss',
            $ctime,
            $crand,
            $account->crand,
            $name,
            $description,
            $inviteToken,
            $signupDeadline,
            $giftDeadline
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to save event.', null);
        }

        $stmt->close();
        return new Response(true, 'Secret Santa event created.', [
            'ctime' => $ctime,
            'crand' => $crand,
            'invite_token' => $inviteToken
        ]);
    }

    public static function listOwnerEvents(): Response
    {
        $authResp = self::requireLogin();
        if (!$authResp->success) {
            return $authResp;
        }

        $account = $authResp->data;
        $conn = self::getConnection();

        $stmt = $conn->prepare(
            "SELECT e.ctime, e.crand, e.name, e.description, e.invite_token, e.signup_deadline, e.gift_deadline, " .
                "COALESCE(p.participant_count, 0) AS participant_count, COALESCE(g.group_count, 0) AS exclusion_group_count " .
                "FROM secret_santa_events e " .
                "LEFT JOIN (" .
                "    SELECT event_ctime, event_crand, COUNT(*) AS participant_count " .
                "    FROM secret_santa_participants " .
                "    GROUP BY event_ctime, event_crand" .
                ") p ON p.event_ctime = e.ctime AND p.event_crand = e.crand " .
                "LEFT JOIN (" .
                "    SELECT event_ctime, event_crand, COUNT(*) AS group_count " .
                "    FROM secret_santa_exclusion_groups " .
                "    GROUP BY event_ctime, event_crand" .
                ") g ON g.event_ctime = e.ctime AND g.event_crand = e.crand " .
                "WHERE e.owner_id = ? " .
                "ORDER BY e.ctime DESC"
        );

        if ($stmt === false) {
            return new Response(false, 'Failed to prepare event lookup.', null);
        }

        $stmt->bind_param('i', $account->crand);

        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to fetch events.', null);
        }

        $result = $stmt->get_result();
        $events = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return new Response(true, 'Owned events fetched.', $events);
    }

    public static function validateInvite(string $inviteToken): Response
    {
        $eventResp = self::fetchEventByToken($inviteToken);
        if (!$eventResp->success) {
            return $eventResp;
        }

        $exclusionResp = self::fetchExclusionGroups($eventResp->data['ctime'], (int)$eventResp->data['crand']);
        if ($exclusionResp->success) {
            $eventResp->data['exclusion_groups'] = $exclusionResp->data;
        } else {
            $eventResp->data['exclusion_groups'] = [];
        }

        $participantsResp = self::fetchParticipantsForEvent($eventResp->data['ctime'], (int)$eventResp->data['crand']);
        if ($participantsResp->success) {
            $groupsByKey = [];
            foreach ($eventResp->data['exclusion_groups'] as $group) {
                $groupsByKey[$group['ctime'] . ':' . $group['crand']] = $group['group_name'];
            }

            $participants = array_map(function ($participant) use ($groupsByKey) {
                $groupKey = $participant['exclusion_group_ctime'] . ':' . $participant['exclusion_group_crand'];
                $groupName = ($participant['exclusion_group_ctime'] && $participant['exclusion_group_crand'])
                    ? ($groupsByKey[$groupKey] ?? null)
                    : null;

                return [
                    'ctime' => $participant['ctime'],
                    'crand' => $participant['crand'],
                    'display_name' => $participant['display_name'],
                    'email' => $participant['email'],
                    'exclusion_group_ctime' => $participant['exclusion_group_ctime'],
                    'exclusion_group_crand' => $participant['exclusion_group_crand'],
                    'exclusion_group_name' => $groupName,
                    'account_id' => $participant['account_id'] ?? null
                ];
            }, $participantsResp->data);

            $eventResp->data['participants'] = $participants;
        } else {
            $eventResp->data['participants'] = [];
        }

        return new Response(true, $eventResp->message, $eventResp->data);
    }

    public static function joinEvent(string $inviteToken, string $displayName, string $email, ?string $exclusionCtime, ?int $exclusionCrand, ?int $accountId = null): Response
    {
        $authResp = self::requireLogin();
        if (!$authResp->success) {
            return $authResp;
        }
        $account = $authResp->data;
        $accountId = $accountId ?? $account->crand;

        $eventResp = self::fetchEventByToken($inviteToken);
        if (!$eventResp->success) {
            return $eventResp;
        }
        $event = $eventResp->data;

        if (self::signupsLocked($event)) {
            return new Response(false, 'Signups are closed for this event.', null);
        }

        $existingParticipant = null;

        if (!is_null($accountId)) {
            $participantByAccountResp = self::fetchParticipantByAccount($event['ctime'], $event['crand'], $accountId);
            if ($participantByAccountResp->success) {
                $existingParticipant = $participantByAccountResp;
            }
        }

        if (is_null($existingParticipant)) {
            $participantResp = self::fetchParticipant($event['ctime'], $event['crand'], $email);
            if ($participantResp->success) {
                $existingParticipant = $participantResp;
            }
        }

        if (!is_null($existingParticipant)) {
            return new Response(true, 'Already joined.', $existingParticipant->data);
        }

        $newRecordId = new RecordId();
        $ctime = $newRecordId->ctime;
        $crand = $newRecordId->crand;

        $conn = self::getConnection();

        $stmt = $conn->prepare(
            "INSERT INTO secret_santa_participants (ctime, crand, event_ctime, event_crand, display_name, email, exclusion_group_ctime, exclusion_group_crand, account_id) " .
            "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if ($stmt === false) {
            return new Response(false, 'Failed to prepare join.', null);
        }

        $stmt->bind_param(
            'sisisssii',
            $ctime,
            $crand,
            $event['ctime'],
            $event['crand'],
            $displayName,
            $email,
            $exclusionCtime,
            $exclusionCrand,
            $accountId
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to join event.', null);
        }

        $stmt->close();
        return new Response(true, 'Joined Secret Santa.', [
            'ctime' => $ctime,
            'crand' => $crand,
            'event' => $event,
            'participant' => [
                'display_name' => $displayName,
                'email' => $email,
                'exclusion_group_ctime' => $exclusionCtime,
                'exclusion_group_crand' => $exclusionCrand,
                'account_id' => $accountId
            ]
        ]);
    }

    public static function removeParticipant(string $eventCtime, int $eventCrand, string $participantCtime, int $participantCrand): Response
    {
        $authResp = self::requireLogin();
        if (!$authResp->success) {
            return $authResp;
        }

        $account = $authResp->data;
        $eventResp = self::fetchEventById($eventCtime, $eventCrand);
        if (!$eventResp->success) {
            return $eventResp;
        }

        $ownerCheck = self::eventOwnerRequired($eventResp->data, $account->crand);
        if (!is_null($ownerCheck)) {
            return $ownerCheck;
        }

        $participantResp = self::fetchParticipantById($eventCtime, $eventCrand, $participantCtime, $participantCrand);
        if (!$participantResp->success) {
            return $participantResp;
        }

        $conn = self::getConnection();

        $assignmentStmt = $conn->prepare(
            "DELETE FROM secret_santa_pairs WHERE event_ctime = ? AND event_crand = ? AND ((giver_participant_ctime = ? AND giver_participant_crand = ?) OR (receiver_participant_ctime = ? AND receiver_participant_crand = ?))"
        );

        if ($assignmentStmt === false) {
            return new Response(false, 'Failed to prepare assignment removal.', null);
        }

        $assignmentStmt->bind_param(
            'sisisi',
            $eventCtime,
            $eventCrand,
            $participantCtime,
            $participantCrand,
            $participantCtime,
            $participantCrand
        );

        if (!$assignmentStmt->execute()) {
            $assignmentStmt->close();
            return new Response(false, 'Failed to clear assignments for participant.', null);
        }

        $assignmentStmt->close();

        $participantStmt = $conn->prepare(
            "DELETE FROM secret_santa_participants WHERE ctime = ? AND crand = ? AND event_ctime = ? AND event_crand = ?"
        );

        if ($participantStmt === false) {
            return new Response(false, 'Failed to prepare participant removal.', null);
        }

        $participantStmt->bind_param('sisi', $participantCtime, $participantCrand, $eventCtime, $eventCrand);

        if (!$participantStmt->execute()) {
            $participantStmt->close();
            return new Response(false, 'Failed to remove participant.', null);
        }

        $participantStmt->close();

        return new Response(true, 'Participant removed from event.', [
            'removed_participant' => [
                'ctime' => $participantCtime,
                'crand' => $participantCrand,
                'email' => $participantResp->data['email'],
                'display_name' => $participantResp->data['display_name']
            ]
        ]);
    }

    public static function upsertExclusionGroup(string $eventCtime, int $eventCrand, string $groupName, ?string $existingGroupCtime, ?int $existingGroupCrand): Response
    {
        $authResp = self::requireLogin();
        if (!$authResp->success) {
            return $authResp;
        }
        $account = $authResp->data;

        $eventResp = self::fetchEventById($eventCtime, $eventCrand);
        if (!$eventResp->success) {
            return $eventResp;
        }
        $event = $eventResp->data;

        $ownerCheck = self::eventOwnerRequired($event, $account->crand);
        if (!is_null($ownerCheck)) {
            return $ownerCheck;
        }

        $conn = self::getConnection();

        if ($existingGroupCtime && $existingGroupCrand) {
            $stmt = $conn->prepare(
                "UPDATE secret_santa_exclusion_groups SET name = ? WHERE ctime = ? AND crand = ? AND event_ctime = ? AND event_crand = ?"
            );

            if ($stmt === false) {
                return new Response(false, 'Failed to prepare group update.', null);
            }

            $stmt->bind_param('ssiii', $groupName, $existingGroupCtime, $existingGroupCrand, $eventCtime, $eventCrand);

            if (!$stmt->execute()) {
                $stmt->close();
                return new Response(false, 'Failed to update exclusion group.', null);
            }

            $stmt->close();
            return new Response(true, 'Exclusion group updated.', [
                'ctime' => $existingGroupCtime,
                'crand' => $existingGroupCrand
            ]);
        }

        $newRecordId = new RecordId();
        $ctime = $newRecordId->ctime;
        $crand = $newRecordId->crand; 

        $stmt = $conn->prepare(
            "INSERT INTO secret_santa_exclusion_groups (ctime, crand, event_ctime, event_crand, group_name) VALUES (?, ?, ?, ?, ?)"
        );

        if ($stmt === false) {
            return new Response(false, 'Failed to prepare group creation.', null);
        }

        $stmt->bind_param('siiss', $ctime, $crand, $eventCtime, $eventCrand, $groupName);

        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to create exclusion group.', null);
        }

        $stmt->close();
        return new Response(true, 'Exclusion group created.', [
            'ctime' => $ctime,
            'crand' => $crand
        ]);
    }

    public static function generatePairs(string $eventCtime, int $eventCrand): Response
    {
        $authResp = self::requireLogin();
        if (!$authResp->success) {
            return $authResp;
        }
        $account = $authResp->data;

        $eventResp = self::fetchEventById($eventCtime, $eventCrand);
        if (!$eventResp->success) {
            return $eventResp;
        }
        $event = $eventResp->data;

        $ownerCheck = self::eventOwnerRequired($event, $account->crand);
        if (!is_null($ownerCheck)) {
            return $ownerCheck;
        }

        if (self::signupsLocked($event) === false) {
            return new Response(false, 'Signups are still open. Wait until after the signup deadline to generate pairs.', null);
        }

        $participantsResp = self::fetchParticipantsForEvent($eventCtime, $eventCrand);
        if (!$participantsResp->success) {
            return $participantsResp;
        }

        $participants = $participantsResp->data;
        if (count($participants) < 2) {
            return new Response(false, 'Not enough participants to generate pairs.', null);
        }

        $pairs = self::generateAssignments($participants);
        if (empty($pairs)) {
            return new Response(false, 'Failed to generate valid pairings. Check exclusion groups.', null);
        }

        $conn = self::getConnection();

        $stmt = $conn->prepare(
            "INSERT INTO secret_santa_pairs (ctime, crand, event_ctime, event_crand, giver_participant_ctime, giver_participant_crand, receiver_participant_ctime, receiver_participant_crand) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if ($stmt === false) {
            return new Response(false, 'Failed to prepare assignment save.', null);
        }

        foreach ($pairs as $pair) {
            $newRecordId = new RecordId();
            $ctime = $newRecordId->ctime;
            $crand = $newRecordId->crand; 
            $stmt->bind_param(
                'siiiiiii',
                $ctime,
                $crand,
                $eventCtime,
                $eventCrand,
                $pair['giver']['ctime'],
                $pair['giver']['crand'],
                $pair['receiver']['ctime'],
                $pair['receiver']['crand']
            );

            if (!$stmt->execute()) {
                $stmt->close();
                return new Response(false, 'Failed to save assignments.', null);
            }
        }

        $stmt->close();
        return new Response(true, 'Assignments generated.', $pairs);
    }

    public static function sendTestAssignmentEmail(
        string $recipientEmail,
        string $recipientName,
        array $giver,
        array $receiver
    ): Response {
        $event = [
            'name' => 'Kickback Kingdom Secret Santa',
            'description' => 'Thanks for joining our annual gift exchange! Here are your official assignment details.',
            'gift_deadline' => (new DateTime('now', new DateTimeZone('UTC')))->modify('+10 days')->format('Y-m-d H:i:s'),
        ];

        return self::sendAssignmentEmail($recipientEmail, $recipientName, $giver, $receiver, $event, true);
    }

    public static function emailAssignments(string $eventCtime, int $eventCrand): Response
    {
        $authResp = self::requireLogin();
        if (!$authResp->success) {
            return $authResp;
        }
        $account = $authResp->data;

        $eventResp = self::fetchEventById($eventCtime, $eventCrand);
        if (!$eventResp->success) {
            return $eventResp;
        }
        $event = $eventResp->data;

        $ownerCheck = self::eventOwnerRequired($event, $account->crand);
        if (!is_null($ownerCheck)) {
            return $ownerCheck;
        }

        $assignmentsResp = self::fetchAssignmentsForEvent($eventCtime, $eventCrand);
        if (!$assignmentsResp->success) {
            return $assignmentsResp;
        }

        $participantsResp = self::fetchParticipantsForEvent($eventCtime, $eventCrand);
        if (!$participantsResp->success) {
            return $participantsResp;
        }

        $participantsById = [];
        foreach ($participantsResp->data as $participant) {
            $participantsById[$participant['ctime'] . ':' . $participant['crand']] = $participant;
        }

        foreach ($assignmentsResp->data as $assignment) {
            $giverKey = $assignment['giver_participant_ctime'] . ':' . $assignment['giver_participant_crand'];
            $receiverKey = $assignment['receiver_participant_ctime'] . ':' . $assignment['receiver_participant_crand'];

            if (!isset($participantsById[$giverKey]) || !isset($participantsById[$receiverKey])) {
                continue;
            }

            $giver = $participantsById[$giverKey];
            $receiver = $participantsById[$receiverKey];

            $emailResp = self::sendAssignmentEmail(
                $giver['email'],
                $giver['display_name'],
                $giver,
                $receiver,
                $event
            );

            if (!$emailResp->success) {
                return $emailResp;
            }
        }

        return new Response(true, 'Assignment emails sent.', null);
    }

    private static function sendAssignmentEmail(
        string $recipientEmail,
        string $recipientName,
        array $giver,
        array $receiver,
        array $event,
        bool $isPreview = false
    ): Response {
        if (empty($recipientEmail)) {
            return new Response(false, 'A recipient email is required.', null);
        }

        $mailer = new PHPMailer(true);
        $credentials = ServiceCredentials::instance();

        $emailContent = self::buildAssignmentEmail($recipientName, $giver, $receiver, $event, $isPreview);

        try {
            $mailer->isSMTP();
            $mailer->SMTPAuth = filter_var($credentials['smtp_auth'], FILTER_VALIDATE_BOOLEAN);
            $mailer->SMTPSecure = $credentials['smtp_secure'];
            $mailer->Host = $credentials['smtp_host'];
            $mailer->Port = intval($credentials['smtp_port']);
            $mailer->Username = $credentials['smtp_username'];
            $mailer->Password = $credentials['smtp_password'];
            $mailer->setFrom($credentials['smtp_from_email'], $credentials['smtp_from_name']);
            $mailer->addReplyTo($credentials['smtp_replyto_email'], $credentials['smtp_replyto_name']);
            $mailer->addAddress($recipientEmail, $recipientName);

            $mailer->isHTML(true);
            $mailer->Subject = $emailContent['subject'];
            $mailer->Body = $emailContent['html'];
            $mailer->AltBody = $emailContent['alt'];
            $mailer->send();
        } catch (Exception $ex) {
            $prefix = $isPreview ? 'Secret Santa preview email failed: ' : 'Failed to send emails: ';
            return new Response(false, $prefix . $ex->getMessage(), null);
        }

        $message = $isPreview ? 'Secret Santa preview email sent.' : 'Assignment emails sent.';
        return new Response(true, $message, null);
    }

    private static function buildAssignmentEmail(
        string $recipientName,
        array $giver,
        array $receiver,
        array $event,
        bool $isPreview
    ): array {
        $eventName = $event['name'] ?? 'Secret Santa';
        $description = trim($event['description'] ?? '');
        $bannerUrl = $event['banner_url'] ?? 'https://kickback-kingdom.com/assets/media/events/1768.png';

        $giftDeadline = $event['gift_deadline'] ?? null;
        $deadlineText = $giftDeadline ?: 'your host’s chosen gift deadline';
        try {
            if (!empty($giftDeadline)) {
                $deadline = new DateTime($giftDeadline, new DateTimeZone('UTC'));
                $deadline->setTimezone(new DateTimeZone('UTC'));
                $deadlineText = $deadline->format('F j, Y \a\t g:i A T');
            }
        } catch (\Throwable $t) {
            $deadlineText = $giftDeadline;
        }

        $receiverEmail = $receiver['email'] ?? '';
        $receiverEmailText = $receiverEmail !== '' ? " ({$receiverEmail})" : '';

        $previewLabel = $isPreview ? '<span style="display:inline-block;margin-bottom:8px;padding:6px 10px;border-radius:999px;background:rgba(255,255,255,0.12);color:#ffd166;font-size:12px;letter-spacing:0.6px;text-transform:uppercase;">Preview of live email</span>' : '';

        $html = <<<HTML
<!doctype html>
<html lang="en-UK">
<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <title>{$eventName} | Secret Santa Assignment</title>
    <meta name="description" content="Secret Santa assignment for {$eventName}">
    <style type="text/css">
        a:hover { text-decoration: underline !important; }
        body { margin: 0; padding: 0; background: #0b1729; font-family: 'Open Sans', Arial, sans-serif; }
    </style>
</head>
<body style="margin:0; padding:0; background:#0b1729;">
    <table width="100%" bgcolor="#0b1729" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding: 32px 12px;">
                <table width="100%" style="max-width: 680px; background: #0f2038; color: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 16px 50px rgba(0,0,0,0.35);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #0d6efd 0%, #842029 80%); padding: 24px 26px; text-align: center;">
                            {$previewLabel}
                            <img src="https://kickback-kingdom.com/assets/images/logo-kk.png" alt="Kickback Kingdom" height="46" style="display:block; margin:0 auto 12px;">
                            <h1 style="margin: 8px 0 0; font-size: 26px; letter-spacing: 0.5px;">{$eventName}</h1>
                            <p style="margin: 10px 0 0; color: rgba(255,255,255,0.78); font-size: 15px;">Your Secret Santa assignment is here!</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background: #0f2038; padding: 0;">
                            <img src="{$bannerUrl}" alt="{$eventName} banner" style="display:block; width:100%; height:auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 26px 28px;">
                            <p style="margin: 0 0 14px; font-size: 16px; color: rgba(255,255,255,0.9);">Hey {$recipientName},</p>
                            <p style="margin: 0 0 18px; font-size: 15px; color: rgba(255,255,255,0.78);">You’re gifting <strong style="color:#fff;">{$receiver['display_name']}{$receiverEmailText}</strong>. Please deliver your gift by <strong style="color:#ffc857;">{$deadlineText}</strong>.</p>
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; background: #102a4c; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; overflow: hidden;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <p style="margin: 0 0 6px; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #67d4ff;">You</p>
                                        <p style="margin: 0; font-size: 20px; font-weight: 600; color: #ffffff;">{$giver['display_name']}</p>
                                    </td>
                                    <td style="padding: 18px 20px; text-align: right;">
                                        <p style="margin: 0 0 6px; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #ffc857;">Recipient</p>
                                        <p style="margin: 0; font-size: 20px; font-weight: 600; color: #ffffff;">{$receiver['display_name']}</p>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 18px 0 12px; font-size: 14px; color: rgba(255,255,255,0.78);">{$description}</p>
                            <p style="margin: 0; font-size: 14px; color: rgba(255,255,255,0.7);">Happy gifting! — The Kickback Kingdom Team</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        $alt = "Hi {$recipientName}, you are gifting {$receiver['display_name']}{$receiverEmailText} for {$eventName}. Please deliver by {$deadlineText}.";

        return [
            'subject' => "Your Secret Santa assignment for {$eventName}",
            'html' => $html,
            'alt' => $alt,
        ];
    }

    private static function fetchEventByToken(string $inviteToken): Response
    {
        $conn = self::getConnection();

        $stmt = $conn->prepare(
            "SELECT * FROM secret_santa_events WHERE invite_token = ?"
        );

        if ($stmt === false) {
            return new Response(false, 'Failed to prepare token lookup.', null);
        }

        $stmt->bind_param('s', $inviteToken);
        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to fetch invite.', null);
        }

        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            return new Response(false, 'Invalid invite token.', null);
        }

        return new Response(true, 'Invite valid.', $row);
    }

    private static function fetchEventById(string $ctime, int $crand): Response
    {
        $conn = self::getConnection();

        $stmt = $conn->prepare(
            "SELECT * FROM secret_santa_events WHERE ctime = ? AND crand = ?"
        );

        if ($stmt === false) {
            return new Response(false, 'Failed to prepare event lookup.', null);
        }

        $stmt->bind_param('si', $ctime, $crand);
        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to fetch event.', null);
        }

        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            return new Response(false, 'Event not found.', null);
        }

        return new Response(true, 'Event located', $row);
    }

    private static function fetchParticipant(string $eventCtime, int $eventCrand, string $email): Response
    {
        $conn = self::getConnection();

        $stmt = $conn->prepare(
            "SELECT * FROM secret_santa_participants WHERE event_ctime = ? AND event_crand = ? AND email = ?"
        );

        if ($stmt === false) {
            return new Response(false, 'Failed to prepare participant lookup.', null);
        }

        $stmt->bind_param('sis', $eventCtime, $eventCrand, $email);
        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to fetch participant.', null);
        }

        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            return new Response(false, 'Participant not found.', null);
        }

        return new Response(true, 'Participant found.', $row);
    }

    private static function fetchParticipantById(string $eventCtime, int $eventCrand, string $participantCtime, int $participantCrand): Response
    {
        $conn = self::getConnection();

        $stmt = $conn->prepare(
            "SELECT * FROM secret_santa_participants WHERE event_ctime = ? AND event_crand = ? AND ctime = ? AND crand = ?"
        );

        if ($stmt === false) {
            return new Response(false, 'Failed to prepare participant lookup.', null);
        }

        $stmt->bind_param('sisi', $eventCtime, $eventCrand, $participantCtime, $participantCrand);
        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to fetch participant.', null);
        }

        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            return new Response(false, 'Participant not found.', null);
        }

        return new Response(true, 'Participant found.', $row);
    }

    private static function fetchParticipantByAccount(string $eventCtime, int $eventCrand, int $accountId): Response
    {
        $conn = self::getConnection();

        $stmt = $conn->prepare(
            "SELECT * FROM secret_santa_participants WHERE event_ctime = ? AND event_crand = ? AND account_id = ?"
        );

        if ($stmt === false) {
            return new Response(false, 'Failed to prepare participant lookup.', null);
        }

        $stmt->bind_param('sii', $eventCtime, $eventCrand, $accountId);
        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to fetch participant.', null);
        }

        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            return new Response(false, 'Participant not found.', null);
        }

        return new Response(true, 'Participant found.', $row);
    }

    private static function fetchParticipantsForEvent(string $eventCtime, int $eventCrand): Response
    {
        $conn = self::getConnection();

        $stmt = $conn->prepare(
            "SELECT * FROM secret_santa_participants WHERE event_ctime = ? AND event_crand = ?"
        );

        if ($stmt === false) {
            return new Response(false, 'Failed to prepare participants fetch.', null);
        }

        $stmt->bind_param('si', $eventCtime, $eventCrand);
        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to fetch participants.', null);
        }

        $result = $stmt->get_result();
        $participants = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        return new Response(true, 'Participants fetched.', $participants);
    }

    private static function fetchExclusionGroups(string $eventCtime, int $eventCrand): Response
    {
        $conn = self::getConnection();

        $stmt = $conn->prepare(
            "SELECT ctime, crand, group_name FROM secret_santa_exclusion_groups WHERE event_ctime = ? AND event_crand = ?"
        );

        if ($stmt === false) {
            return new Response(false, 'Failed to prepare exclusion groups fetch.', null);
        }

        $stmt->bind_param('si', $eventCtime, $eventCrand);
        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to fetch exclusion groups.', null);
        }

        $result = $stmt->get_result();
        $groups = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        return new Response(true, 'Exclusion groups fetched.', $groups);
    }

    private static function fetchAssignmentsForEvent(string $eventCtime, int $eventCrand): Response
    {
        $conn = self::getConnection();

        $stmt = $conn->prepare(
            "SELECT * FROM secret_santa_pairs WHERE event_ctime = ? AND event_crand = ?"
        );

        if ($stmt === false) {
            return new Response(false, 'Failed to prepare assignment fetch.', null);
        }

        $stmt->bind_param('si', $eventCtime, $eventCrand);
        if (!$stmt->execute()) {
            $stmt->close();
            return new Response(false, 'Failed to fetch assignments.', null);
        }

        $result = $stmt->get_result();
        $assignments = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        return new Response(true, 'Assignments fetched.', $assignments);
    }

    private static function generateAssignments(array $participants): array
    {
        $maxAttempts = 50;
        $participantCount = count($participants);

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $shuffled = $participants;
            shuffle($shuffled);

            $pairs = [];
            $valid = true;

            for ($i = 0; $i < $participantCount; $i++) {
                $giver = $shuffled[$i];
                $receiver = $shuffled[($i + 1) % $participantCount];

                if ($giver['ctime'] === $receiver['ctime'] && $giver['crand'] === $receiver['crand']) {
                    $valid = false;
                    break;
                }

                if (self::inExclusionGroup($giver, $receiver)) {
                    $valid = false;
                    break;
                }

                $pairs[] = [
                    'giver' => $giver,
                    'receiver' => $receiver
                ];
            }

            if ($valid) {
                return $pairs;
            }
        }

        return [];
    }

    private static function inExclusionGroup(array $giver, array $receiver): bool
    {
        return isset($giver['exclusion_group_ctime'], $giver['exclusion_group_crand'], $receiver['exclusion_group_ctime'], $receiver['exclusion_group_crand'])
            && $giver['exclusion_group_ctime'] === $receiver['exclusion_group_ctime']
            && $giver['exclusion_group_crand'] === $receiver['exclusion_group_crand'];
    }

    private static function generateInviteToken(): string
    {
        return bin2hex(random_bytes(8));
    }

    private static function eventOwnerRequired(array $eventRow, int $accountId): ?Response
    {
        if ((int)$eventRow['owner_id'] !== $accountId) {
            return new Response(false, 'Only the event owner can perform this action.', null);
        }

        return null;
    }

    private static function signupsLocked(array $eventRow): bool
    {
        $deadline = new DateTime($eventRow['signup_deadline']);
        $now = new DateTime('now', new DateTimeZone('UTC'));
        return $now > $deadline;
    }

    private static function getConnection(): \mysqli
    {
        return Database::getConnection();
    }
}
