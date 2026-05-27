<?php

declare(strict_types=1);

namespace Pledg\SyliusPaymentPlugin\Notification\Collector;

class ErrorNotificationValidator implements ValidatorInterface
{
    private const ERROR_STATES = [
        'BI_CANCELLED', 'BI_KO', 'BI_SCORING_KO', 'CONNECTOR_KO',
        'PRIMARY_KO', 'REVENUE_KO', 'SCORING_KO', 'TMX_SCORING_KO',
        'VCP_KO', 'FIRCOSOFT_FILTER_KO', 'FICP_FILTER_KO',
        'RULE_ENGINE_FILTERS_KO', 'RULE_ENGINE_OPEN_BANKING_KO',
        'COVERAGE_KO', 'MERCHANT_TIMEOUT', 'IDENTIFICATION_KO', 'SIGNATURE_KO',
    ];

    public function supports(array $content): bool
    {
        if (!isset($content['reference'])) {
            return false;
        }

        if (isset($content['state'])) {
            $state = strtoupper((string) $content['state']);

            return \in_array($state, self::ERROR_STATES, true)
                || str_ends_with($state, '_KO');
        }

        if (isset($content['acceptance_state'])) {
            $acceptance = strtoupper((string) $content['acceptance_state']);

            return \in_array($acceptance, ['REJECTED', 'ABANDONED'], true);
        }

        return false;
    }

    public function validate(array $content): bool
    {
        return isset($content['reference']) && '' !== $content['reference'];
    }
}
