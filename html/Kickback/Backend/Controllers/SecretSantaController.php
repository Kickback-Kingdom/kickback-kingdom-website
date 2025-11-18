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
            "INSERT INTO secret_santa_assignments (ctime, crand, event_ctime, event_crand, giver_ctime, giver_crand, receiver_ctime, receiver_crand) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
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

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->Port = 587;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPAuth = true;
            $mail->Username = ServiceCredentials::GMAIL_USERNAME;
            $mail->Password = ServiceCredentials::GMAIL_PASSWORD;
            $mail->setFrom(ServiceCredentials::GMAIL_USERNAME, 'Kickback Secret Santa');

            foreach ($assignmentsResp->data as $assignment) {
                $giverKey = $assignment['giver_ctime'] . ':' . $assignment['giver_crand'];
                $receiverKey = $assignment['receiver_ctime'] . ':' . $assignment['receiver_crand'];

                if (!isset($participantsById[$giverKey]) || !isset($participantsById[$receiverKey])) {
                    continue;
                }

                $giver = $participantsById[$giverKey];
                $receiver = $participantsById[$receiverKey];

                $mail->clearAddresses();
                $mail->addAddress($giver['email'], $giver['display_name']);
                $mail->Subject = "Your Secret Santa Assignment for {$event['name']}";
                $mail->Body = "Hi {$giver['display_name']}, you will be gifting {$receiver['display_name']} ({$receiver['email']}). Gift deadline: {$event['gift_deadline']}";
                $mail->send();
            }
        } catch (Exception $ex) {
            return new Response(false, 'Failed to send emails: ' . $ex->getMessage(), null);
        }

        return new Response(true, 'Assignment emails sent.', null);
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
            "SELECT * FROM secret_santa_assignments WHERE event_ctime = ? AND event_crand = ?"
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
