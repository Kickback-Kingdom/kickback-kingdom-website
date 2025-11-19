<?php
declare(strict_types=1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/..")) . "/Kickback/init.php");

use Kickback\Backend\Controllers\SecretSantaController;
use Kickback\Services\Session;

/**
 * Invoke a private static method on SecretSantaController.
 */
function callSecretSantaPrivate(string $method, array $args = [])
{
    $refMethod = new ReflectionMethod(SecretSantaController::class, $method);
    $refMethod->setAccessible(true);
    return $refMethod->invokeArgs(null, $args);
}

/**
 * Simple assertion helper that throws an AssertionError with a custom message.
 */
function ensure(bool $condition, string $message): void
{
    if (!$condition) {
        throw new AssertionError($message);
    }
}

try {
    $account = null;
    ensure(
        Session::readCurrentAccountInto($account) && !is_null($account),
        'You must be logged in to trigger the Secret Santa test email.'
    );

    ensure(!empty($account->email), 'Logged in account does not have a valid email address.');

    $participantsWithGroups = [
        ['ctime' => 't1', 'crand' => 1, 'display_name' => 'Eric', 'exclusion_group_ctime' => 'g1', 'exclusion_group_crand' => 101],
        ['ctime' => 't2', 'crand' => 2, 'display_name' => 'Giovanna', 'exclusion_group_ctime' => 'g1', 'exclusion_group_crand' => 101],
        ['ctime' => 't3', 'crand' => 3, 'display_name' => 'Helen', 'exclusion_group_ctime' => 'g1', 'exclusion_group_crand' => 101],
        ['ctime' => 't4', 'crand' => 4, 'display_name' => 'Alex', 'exclusion_group_ctime' => 'g2', 'exclusion_group_crand' => 202],
        ['ctime' => 't5', 'crand' => 5, 'display_name' => 'Carley', 'exclusion_group_ctime' => 'g2', 'exclusion_group_crand' => 202],
        ['ctime' => 't6', 'crand' => 6, 'display_name' => 'Jp', 'exclusion_group_ctime' => 'g3', 'exclusion_group_crand' => 303],
        ['ctime' => 't7', 'crand' => 7, 'display_name' => 'Luisa', 'exclusion_group_ctime' => 'g3', 'exclusion_group_crand' => 303],
    ];

    $pairs = callSecretSantaPrivate('generateAssignments', [$participantsWithGroups]);

    ensure(!empty($pairs), 'Expected to generate valid assignments when exclusion groups allow it.');

    echo "Generated assignments:\n";
    foreach ($pairs as $pair) {
        $giverName = $pair['giver']['display_name'];
        $receiverName = $pair['receiver']['display_name'];
        echo "- {$giverName} → {$receiverName}\n";
    }

    foreach ($pairs as $pair) {
        ensure(
            $pair['giver']['ctime'] !== $pair['receiver']['ctime'] || $pair['giver']['crand'] !== $pair['receiver']['crand'],
            'No participant should be assigned to themselves.'
        );

        $inSameGroup = callSecretSantaPrivate('inExclusionGroup', [$pair['giver'], $pair['receiver']]);
        ensure(!$inSameGroup, 'Assignments must not pair members of the same exclusion group.');
    }

    $testPair = $pairs[array_key_first($pairs)];
    $testGiver = $testPair['giver'];
    $testReceiver = $testPair['receiver'];

    $recipientName = trim(($account->firstName ?? '') . ' ' . ($account->lastName ?? ''));
    $recipientName = $recipientName !== '' ? $recipientName : $account->username;

    $emailResp = SecretSantaController::sendTestAssignmentEmail(
        $account->email,
        $recipientName,
        $testGiver,
        $testReceiver
    );

    ensure($emailResp->success, $emailResp->message);

    $blockedParticipants = [
        ['ctime' => 't10', 'crand' => 10, 'display_name' => 'Finn', 'exclusion_group_ctime' => 'g-lock', 'exclusion_group_crand' => 1],
        ['ctime' => 't11', 'crand' => 11, 'display_name' => 'Gwen', 'exclusion_group_ctime' => 'g-lock', 'exclusion_group_crand' => 1],
        ['ctime' => 't12', 'crand' => 12, 'display_name' => 'Hale', 'exclusion_group_ctime' => 'g-lock', 'exclusion_group_crand' => 1],
    ];

    $blockedPairs = callSecretSantaPrivate('generateAssignments', [$blockedParticipants]);
    ensure(empty($blockedPairs), 'Should not generate assignments when exclusion groups prevent valid pairings.');

    echo "\nBlocked participants example (all in the same exclusion group):\n";
    foreach ($blockedParticipants as $participant) {
        echo "- {$participant['display_name']} (group {$participant['exclusion_group_ctime']})\n";
    }

    echo "All exclusion group unit tests passed.\n";
    echo "A test assignment email was sent to {$account->email}.\n";
    exit(0);
} catch (AssertionError $e) {
    echo "Secret Santa exclusion group test failed: {$e->getMessage()}\n";
    exit(1);
} catch (Exception $e) {
    echo "Secret Santa test email failed: {$e->getMessage()}\n";
    exit(1);
}
