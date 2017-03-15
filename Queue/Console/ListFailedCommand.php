<?php
namespace Core\Queue\Console;

use Illuminate\Queue\Console\ListFailedCommand as BaseListFailedCommand;
use Illuminate\Support\Arr;
class ListFailedCommand extends BaseListFailedCommand
{  
	protected $headers = ['ID', 'Queue', 'Class', 'Failed At'];
	/**
     * Parse the failed job row.
     *
     * @param  array  $failed
     * @return array
     */
    protected function parseFailedJob(array $failed)
    {
        $row = array_values(Arr::except($failed, ['payload', 'exception']));
        array_splice($row, 3, 0, $this->extractJobName($failed['payload']));
        $row[0] = $row[5];
        array_splice($row, 5);
        array_splice($row, 1,1);
        return $row;
    }
    /**
     * Extract the failed job name from payload.
     *
     * @param  string  $payload
     * @return string|null
     */
    private function extractJobName($payload)
    {
        $payload = json_decode($payload, true);

        if ($payload && (! isset($payload['data']['command']))) {
            return Arr::get($payload, 'job');
        } elseif ($payload && isset($payload['data']['command'])) {
            return $this->matchJobName($payload);
        }
    }
}
