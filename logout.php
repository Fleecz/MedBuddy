<?php
session_start();
require_once __DIR__ . '/lib/helpers.php';
session_unset();
session_destroy();
redirect_to('login.php');