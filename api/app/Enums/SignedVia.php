<?php

namespace App\Enums;

/** Canal por el que se registró la firma. */
enum SignedVia: string
{
    case Link = 'link';
    case Webhook = 'webhook';
}
