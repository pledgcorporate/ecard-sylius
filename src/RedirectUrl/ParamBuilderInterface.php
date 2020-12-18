<?php


namespace Pledg\SyliusPaymentPlugin\RedirectUrl;

interface ParamBuilderInterface
{
    public function build(): array;
}
