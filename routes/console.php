<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('issues:flag-overdue')->hourly();
