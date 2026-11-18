<?php

namespace App\Enums;

enum IssueCategory: string
{
    case Billing = 'billing';
    case Technical = 'technical';
    case Account = 'account';
    case FeatureRequest = 'feature_request';
    case Other = 'other';
}
