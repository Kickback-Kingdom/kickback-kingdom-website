<?php
require_once(__DIR__ . '/../engine.php');

use Kickback\Backend\Controllers\ScheduleController;
use Kickback\Backend\Models\Response;

OnlyGET();

$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year  = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

$suggestions = ScheduleController::getSuggestedDates($month, $year);

return new Response(true, 'Suggested dates generated', $suggestions);
?>
