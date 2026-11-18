<?php

return [

    /*
    | An open or in progress issue older than this many days is overdue
    | and gets needs_attention = true.
    */

    'overdue_days' => (int) env('ISSUE_OVERDUE_DAYS', 3),

];
