<?php

try {
    Targets::build($argv);
} catch (Exception $e) {
    echo $e->getMessage();
}

class Targets
{

    public static function build(array $argv): void
    {
        if (!isset($argv[1])) {
            throw new \Exception('Missing Smokeping target style.');
        }

        match ($argv[1]) {
            'default' => self::default(self::prepare()),
            default => self::default(self::prepare()),
        };
    }

    public static function prepare(): array
    {
        $preparedData = [];
        foreach (glob(__DIR__.'/../targets/*/*.json') as $country) {
            $countryData = pathinfo($country);
            $continentCode = substr($countryData['dirname'], -2);
            $countryCode = $countryData['filename'];
            $preparedData[$continentCode][$countryCode] = json_decode(file_get_contents($country), true);
        }
        return $preparedData;
    }

    public static function default(array $data): void
    {
        $countries = json_decode(file_get_contents(__DIR__.'/countries.json'), true);
        $smokePings = '# SmokePing Instances'.PHP_EOL.'| Network | ASN | Continent | Country | City | SmokePing |'.PHP_EOL.'|:---:|---|---|---|---|---|'.PHP_EOL;
        $targetConfig = '# Eranium SmokePing Targets Project'.PHP_EOL.'# Generated on '.date('Y-m-d H:i:s').PHP_EOL.'# https://github.com/eranium/smokeping-targets'.PHP_EOL.PHP_EOL;
        $targetConfig .= '*** Targets ***'.PHP_EOL.PHP_EOL.'probe = FPing'.PHP_EOL.'menu = Top'.PHP_EOL.'title = '.($_SERVER['argv'][2] ?? 'Example').' SmokePing'.PHP_EOL.'remark = Welcome to the '.($_SERVER['argv'][2] ?? 'Example').' SmokePing. This page will show more insights about network latency and potential issues. The targets are based on an <a href="https://github.com/eranium/smokeping-targets" target="_blank">open source and community-driven project</a>.'.PHP_EOL.PHP_EOL;

        $anycast = json_decode(file_get_contents(__DIR__.'/../targets/anycast.json'), true);
        $targetConfig .= '#####################'.PHP_EOL.PHP_EOL.'+ Anycast'.PHP_EOL;
        $targetConfig .= 'menu = Anycast'.PHP_EOL;
        $targetConfig .= 'title = Anycast'.PHP_EOL.PHP_EOL;
        foreach ($anycast as $server) {
            $targetConfig .= '++ '.str_replace(['.', ' '], '-', $server['name']).PHP_EOL;
            $targetConfig .= 'menu = '.$server['name'].' | AS'.$server['asn'].PHP_EOL;
            $targetConfig .= 'title = '.$server['name'].' | AS'.$server['asn'].PHP_EOL;
            $targetConfig .= 'host = '.$server['ipv4'].PHP_EOL.PHP_EOL;
            if ($server['ipv6']) {
                $targetConfig .= '++ '.str_replace(['.', ' '], '-', $server['name']).'-IPV6'.PHP_EOL;
                $targetConfig .= 'menu = '.$server['name'].' | AS'.$server['asn'].' IPv6'.PHP_EOL;
                $targetConfig .= 'title = '.$server['name'].' | AS'.$server['asn'].' IPv6'.PHP_EOL;
                $targetConfig .= 'probe = FPing6'.PHP_EOL;
                $targetConfig .= 'host = '.$server['ipv6'].PHP_EOL.PHP_EOL;
            }
        }

        $root = json_decode(file_get_contents(__DIR__.'/../targets/root.json'), true);
        $targetConfig .= '#####################'.PHP_EOL.PHP_EOL.'+ RootServers'.PHP_EOL;
        $targetConfig .= 'menu = Root Servers'.PHP_EOL;
        $targetConfig .= 'title = Root Servers'.PHP_EOL.PHP_EOL;
        foreach ($root as $server) {
            $targetConfig .= '++ '.str_replace(['.', ' '], '-', $server['name']).PHP_EOL;
            $targetConfig .= 'menu = '.$server['name'].PHP_EOL;
            $targetConfig .= 'title = '.$server['name'].PHP_EOL;
            $targetConfig .= 'host = '.$server['ipv4'].PHP_EOL.PHP_EOL;
            if ($server['ipv6']) {
                $targetConfig .= '++ '.str_replace(['.', ' '], '-', $server['name']).'-IPV6'.PHP_EOL;
                $targetConfig .= 'menu = '.$server['name'].' IPv6'.PHP_EOL;
                $targetConfig .= 'title = '.$server['name'].' IPv6'.PHP_EOL;
                $targetConfig .= 'probe = FPing6'.PHP_EOL;
                $targetConfig .= 'host = '.$server['ipv6'].PHP_EOL.PHP_EOL;
            }
        }
        foreach ($data as $continentCode => $continent) {
            $targetConfig .= '#####################'.PHP_EOL.PHP_EOL.'+ '.$continentCode.PHP_EOL;
            $targetConfig .= 'menu = '.$continentCode.' - '.self::continentName($continentCode).PHP_EOL;
            $targetConfig .= 'title = '.$continentCode.' - '.self::continentName($continentCode).PHP_EOL.PHP_EOL;
            foreach ($continent as $countryCode => $country) {
                $targetConfig .= '++ '.$countryCode.PHP_EOL;
                $targetConfig .= 'menu = '.$countryCode.' - '.$countries[$countryCode].PHP_EOL;
                $targetConfig .= 'title = '.$countryCode.' - '.$countries[$countryCode].PHP_EOL.PHP_EOL;
                foreach ($country as $targets) {
                    $targetConfig .= '+++ '.$targets['asn'].PHP_EOL;
                    $targetConfig .= 'menu = '.$targets['name'].' | AS'.$targets['asn'].PHP_EOL;
                    $targetConfig .= 'title = '.$targets['name'].' | AS'.$targets['asn'].PHP_EOL;
                    $targetConfig .= 'host = '.$targets['ipv4'].PHP_EOL.PHP_EOL;
                    if ($targets['ipv6']) {
                        $targetConfig .= '+++ '.$targets['asn'].'-IPV6'.PHP_EOL;
                        $targetConfig .= 'menu = '.$targets['name'].' | AS'.$targets['asn'].' IPv6'.PHP_EOL;
                        $targetConfig .= 'title = '.$targets['name'].' | AS'.$targets['asn'].' IPv6'.PHP_EOL;
                        $targetConfig .= 'probe = FPing6'.PHP_EOL;
                        $targetConfig .= 'host = '.$targets['ipv6'].PHP_EOL.PHP_EOL;
                    }
                    if ($targets['smokeping'] ?? false) {
                        $smokePings .= '|'.$targets['name'].'|'.$targets['asn'].'|'.$continentCode.'|'.$countryCode.'|'.$targets['location'].'|'.$targets['smokeping'].'|'.PHP_EOL;
                    }
                }
            }
        }
        file_put_contents(__DIR__.'/../config/Targets', $targetConfig);
        file_put_contents(__DIR__.'/../INSTANCES.md', $smokePings);
    }

    private static function continentName(string $continentCode): string
    {
        return match ($continentCode) {
            'AF' => 'Africa',
            'AS' => 'Asia',
            'EU' => 'Europe',
            'NA' => 'North America',
            'OC' => 'Oceania',
            'SA' => 'South America',
            'AN' => 'Antarctica'
        };
    }
}
