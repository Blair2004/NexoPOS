<?php
namespace App\Enums;

enum DriverStatusEnum: string {
    case Available = 'available';
    case Busy = 'busy';
    case Offline = 'offline';
    case Disabled = 'disabled';
}