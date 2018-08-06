<?php

namespace Concrete\Core\File\Command;

use Concrete\Core\Entity\File\File;
use Concrete\Core\Foundation\Command\CommandInterface;
use Concrete\Core\Foundation\Queue\Batch\BatchProcessFactoryInterface;
use Concrete\Core\Foundation\Queue\Batch\Command\BatchableCommandInterface;
use Concrete\Core\Foundation\Queue\Batch\Response\BatchProcessorResponse;
use League\Tactician\Bernard\QueueableCommand;

class RescanFileBatchProcessFactory implements BatchProcessFactoryInterface
{

    public function getBatchHandle()
    {
        return 'rescan_file';
    }

    public function getCommands($files) : array
    {
        $commands = [];
        foreach($files as $file) {
            $commands[] = new RescanFileCommand($file->getFileID());
        }
        return $commands;
    }
}