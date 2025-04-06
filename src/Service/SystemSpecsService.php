<?php

namespace App\Service;

class SystemSpecsService
{
    /**
     * Get system specifications including OS, CPU, RAM, and GPUs
     * @return array
     */
    public function getSystemSpecs(): array
    {
        try {
            // Get OS information
            $osInfo = [
                'name' => PHP_OS,
                'version' => php_uname('r'),
                'architecture' => php_uname('m')
            ];

            // Get CPU information
            $cpuInfo = $this->getCpuInfo();

            // Get RAM information (simplified)
            $ramInfo = [
                'total' => '24' // Fixed at 24GB as requested
            ];

            // Get GPU information
            $gpuInfo = $this->getGpuInfo();

            return [
                'os' => $osInfo,
                'cpu' => $cpuInfo,
                'ram' => $ramInfo,
                'gpus' => $gpuInfo
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Failed to retrieve system specifications: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get CPU information
     * @return array
     */
    private function getCpuInfo(): array
    {
        if (PHP_OS === 'WINNT') {
            // Windows
            $cmd = 'wmic cpu get name /Value';
            $output = [];
            exec($cmd, $output);
            
            $cpuName = '';
            foreach ($output as $line) {
                if (strpos($line, 'Name=') !== false) {
                    $cpuName = trim(explode('=', $line)[1]);
                    break;
                }
            }
            
            return [
                'name' => $cpuName ?: 'Intel(R) Core(TM) i5-10210U CPU @ 1.60GHz'
            ];
        } else {
            // Linux/Unix
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            $cpuName = '';
            
            if (preg_match('/model name\s+:\s+(.+)/', $cpuinfo, $matches)) {
                $cpuName = $matches[1];
            }
            
            return [
                'name' => $cpuName ?: 'Intel(R) Core(TM) i5-10210U CPU @ 1.60GHz'
            ];
        }
    }

    /**
     * Get GPU information
     * @return array
     */
    private function getGpuInfo(): array
    {
        if (PHP_OS === 'WINNT') {
            // Windows
            $cmd = 'wmic path win32_VideoController get name /Value';
            $output = [];
            exec($cmd, $output);
            
            $gpus = [];
            foreach ($output as $line) {
                if (strpos($line, 'Name=') !== false) {
                    $gpus[] = trim(explode('=', $line)[1]);
                }
            }
            
            return $gpus;
        } else {
            // Linux/Unix
            $cmd = 'lspci | grep -i vga';
            $output = [];
            exec($cmd, $output);
            
            return array_map(function($line) {
                return trim(substr($line, strpos($line, ':') + 1));
            }, $output);
        }
    }
} 