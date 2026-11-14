<?php
// Branded error page for production. No exception details are ever shown here.
$errorCode  = 'Oops';
$errorTitle = 'Something went wrong';
$errorText  = 'We hit a problem on our side. Please try again in a moment. If it keeps happening, contact your branch.';

require __DIR__ . DIRECTORY_SEPARATOR . '_branded.php';
