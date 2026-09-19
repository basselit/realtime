<?php

namespace Codatsoft\Realtime\Voice\Database;

use Codatsoft\Codatbase\Database\TDatabase;
use Codatsoft\Codatbase\Database\TDBMappers;

class VoiDatabase extends TDatabase
{
    public VoiRead $reader;
    public VoiSave $saver;
    public VoiConvert $converter;

    public function __construct(string $mapper = TDBMappers::ELOQUENT)
    {
        $this->converter = new VoiConvert();
        $this->reader = new VoiRead($this->converter, $mapper);
        $this->saver = new VoiSave($this->converter, $mapper);
        parent::__construct($this->reader, $this->saver, $this->converter);

    }


}
