<?php
namespace Shopware\Connect\Struct\Verificator\Change;

use Shopware\Connect\Struct\Verificator\Change;
use Shopware\Connect\Struct\VerificatorDispatcher;
use Shopware\Connect\Struct;

class MakeMainVariant extends Change
{
    /**
     * Method to verify a structs integrity
     *
     * Throws a RuntimeException if the struct does not verify.
     *
     * @param VerificatorDispatcher $dispatcher
     * @param Struct $struct
     * @return void
     */
    protected function verifyDefault(VerificatorDispatcher $dispatcher, Struct $struct)
    {
        if ($struct->groupId === null) {
            throw new \RuntimeException('Property $groupId must be string.');
        }
        if ($struct->sourceId === null) {
            throw new \Shopware\Connect\Exception\VerificationFailedException('Property $sourceId must be set.');
        }
    }
}