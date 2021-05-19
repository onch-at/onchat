<?php

declare(strict_types=1);

namespace app\entity;

use app\constant\TipsType;
use app\contract\TipsMessage;

class JoinRoomTipsMessage extends TipsMessage
{
  public $type = TipsType::JOIN_ROOM;
}
