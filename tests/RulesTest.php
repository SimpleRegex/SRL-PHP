<?php

namespace SRLTests;

use SRL\SRL;

class RulesTest extends TestCase
{
    protected $assertionCount = 0;

    public function testRules()
    {
        foreach (glob(__DIR__ . '/rules/*.rule') as $ruleFile) {
            $this->runAssertions($this->buildData(file($ruleFile, FILE_IGNORE_NEW_LINES)));
        }
    }

    protected function runAssertions(array $data)
    {
        $assertionMade = false;
        $this->assertNotEmpty($data['srl'], 'SRL for rule is empty. Invalid rule.');

        $query = new SRL($data['srl']);

        foreach ($data['matches'] as $match) {
            $this->assertTrue(
                $query->isMatching($match),
                "Failed asserting that '{$data['srl']}' matches '$match'"
            );
            $assertionMade = true;
        }

        foreach ($data['no_matches'] as $noMatch) {
            $this->assertFalse(
                $query->isMatching($noMatch),
                "Failed asserting that '{$data['srl']}' does not match '$noMatch'"
            );
            $assertionMade = true;
        }

        foreach ($data['captures'] as $test => $expected) {
            $matches = $query->getMatches($test);
            $this->assertCount(count($expected), $matches, "Invalid match count for test '$test' on '{$data['srl']}'.");
            foreach ($matches as $k => $capture) {
                $this->assertEquals(
                    $expected[$k],
                    $capture->all(),
                    "The capture group did not return the expected results for test '$test' on '{$data['srl']}'"
                );
            }
            $assertionMade = true;
        }

        $this->assertTrue($assertionMade, "No assertion for '{$data['srl']}'. Invalid rule.");
    }

    protected function buildData(array $lines) : array
    {
        $data = ['srl' => null, 'matches' => [], 'no_matches' => [], 'captures' => []];
        $inCapture = false;

        foreach ($lines as $line) {
            if (empty($line) || strpos($line, '#') === 0) {
                // Ignore comments and empty lines.
                continue;
            }

            if ($inCapture && substr($line, 0, 1) !== '-') {
                // Reset capture flag.
                $inCapture = false;
            }

            if (strpos($line, 'srl: ') === 0) {
                $data['srl'] = substr($line, 5);
            } elseif (strpos($line, 'match: "') === 0) {
                $data['matches'][] = $this->applySpecialChars(substr($line, 8, -1));
            } elseif (strpos($line, 'no match: "') === 0) {
                $data['no_matches'][] = $this->applySpecialChars(substr($line, 11, -1));
            } elseif (strpos($line, 'capture for "') === 0 && substr($line, -2, 2) === '":') {
                $inCapture = substr($line, 13, -2);
            } elseif ($inCapture && substr($line, 0, 1) === '-') {
                $split = explode(': ', substr($line, 1));
                $data['captures'][$inCapture][(int)$split[0]][trim($split[1])] = substr($split[2], 1, -1);
            }
        }

        return $data;
    }

    protected function applySpecialChars(string $string) : string
    {
        return str_replace(['\n', '\t'], ["\n", "\t"], $string);
    }
}