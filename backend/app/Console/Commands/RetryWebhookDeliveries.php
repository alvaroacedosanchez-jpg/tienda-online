<?php

namespace App\Console\Commands;

use App\Services\Integration\IntegrationOutbox;
use Illuminate\Console\Command;

/**
 * Reintenta los eventos de la bandeja de salida hacia n8n que siguen pendientes.
 * Lo lanza el programador cada minuto (routes/console.php), con el cron del panel de DonDominio.
 *
 * Con --fallidos, antes vuelve a poner en cola los que agotaron sus intentos o recibieron un error
 * definitivo (mismo event_id y mismo cuerpo: n8n y el back-office los reconocen si ya los tenían).
 */
class RetryWebhookDeliveries extends Command
{
    protected $signature = 'integracion:reintentar
        {--fallidos : Vuelve a poner en cola las entregas fallidas antes de reintentar}
        {--limit=50 : Máximo de entregas por ejecución}';

    protected $description = 'Reintenta los eventos pendientes de enviar a n8n (bandeja de salida)';

    public function handle(IntegrationOutbox $outbox): int
    {
        if ($this->option('fallidos')) {
            $this->info('Fallidas que vuelven a la cola: '.$outbox->requeueFailed());
        }

        $summary = $outbox->retryDue((int) $this->option('limit'));

        $this->info(sprintf(
            'Enviados: %d · siguen pendientes: %d · fallidos: %d · descartados: %d',
            $summary['enviado'] ?? 0,
            $summary['pendiente'] ?? 0,
            $summary['fallido'] ?? 0,
            $summary['descartado'] ?? 0,
        ));

        return self::SUCCESS;
    }
}
