<?php

namespace Codatsoft\Realtime\Voice\Models\DBMapped;

use Codatsoft\Codatbase\Base\PBases;
use Illuminate\Support\Collection;

class TVoiceCalls extends PBases
{
    public function current(): TVoiceCall
    {
        return $this->elements[$this->position];
    }

    public function add(TVoiceCall $one): void
    {
        $this->elements[] = $one;
    }

    public static function fromModels(Collection $models): TVoiceCalls
    {
        $list = new self();

        foreach ($models as $model)
        {
            $list->add(TVoiceCall::fromModel($model));
        }

        return $list;
    }

}
