<?php

namespace BaksDev\Avito\Promotion\Entity\Modify;

use BaksDev\Core\Type\Modify\ModifyAction;

interface AvitoPromotionEventModifyInterface
{
    public function getAction(): ModifyAction;
}