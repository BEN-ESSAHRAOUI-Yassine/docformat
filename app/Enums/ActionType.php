<?php

namespace App\Enums;

enum ActionType: string
{
    case HeadingAssigned = 'heading_assigned';
    case StyleFixed = 'style_fixed';
    case Merged = 'merged';
    case Renumbered = 'renumbered';
    case CaptionAdded = 'caption_added';
    case CitationLinked = 'citation_linked';
    case PageBreakAdded = 'page_break_added';
    case PageBreakRemoved = 'page_break_removed';
    case IssueAccepted = 'issue_accepted';
    case IssueRejected = 'issue_rejected';
    case IssueEdited = 'issue_edited';
    case IssueIgnored = 'issue_ignored';
    case BulkResolved = 'bulk_resolved';
    case SecurityEvent = 'security_event';
}
