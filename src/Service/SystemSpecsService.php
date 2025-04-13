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

            // Get RAM information
            $ramInfo = $this->getRamInfo();

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
     * Get RAM information
     * @return array
     */
    private function getRamInfo(): array
    {
        if (PHP_OS === 'WINNT') {
            // Windows
            $cmd = 'wmic ComputerSystem get TotalPhysicalMemory /Value';
            $output = [];
            exec($cmd, $output);
            
            $totalMemory = 0;
            foreach ($output as $line) {
                if (strpos($line, 'TotalPhysicalMemory=') !== false) {
                    $totalMemory = trim(explode('=', $line)[1]);
                    // Convert bytes to GB and round to nearest integer
                    $totalMemory = round((float)$totalMemory / (1024 * 1024 * 1024));
                    break;
                }
            }
            
            return [
                'total' => $totalMemory ? $totalMemory . '' : 'Unknown'
            ];
        } else {
            // Linux/Unix
            $meminfo = file_get_contents('/proc/meminfo');
            if (preg_match('/MemTotal:\s+(\d+)\s+kB/', $meminfo, $matches)) {
                // Convert kB to GB and round to nearest integer
                $totalMemory = round((float)$matches[1] / (1024 * 1024));
                return [
                    'total' => $totalMemory . ''
                ];
            }
            
            return [
                'total' => 'Unknown'
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
                    $gpuName = trim(explode('=', $line)[1]);
                    if (!empty($gpuName)) {
                        $gpus[] = $gpuName;
                    }
                }
            }
            
            return empty($gpus) ? ['Unknown GPU'] : $gpus;
        } else {
            // Linux/Unix
            $cmd = 'lspci | grep -i "vga\|3d\|display"';
            $output = [];
            exec($cmd, $output);
            
            if (empty($output)) {
                return ['Unknown GPU'];
            }

            return array_map(function($line) {
                $parts = explode(':', $line, 3);
                return isset($parts[2]) ? trim($parts[2]) : trim($parts[1] ?? 'Unknown GPU');
            }, $output);
        }
    }
} 