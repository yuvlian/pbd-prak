<?php

require 'config.php';

$_SESSION = [];
session_destroy();

redirect('login.php');
