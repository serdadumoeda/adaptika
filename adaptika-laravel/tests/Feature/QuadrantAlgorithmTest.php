<?php

namespace Tests\Feature;

use App\Models\Peserta;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuadrantAlgorithmTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        // Set the default threshold to 60 for predictability in tests
        Setting::updateOrCreate(
            ['key' => 'threshold_k2'],
            ['value' => '60']
        );
    }

    /**
     * Group: Kejuruan Teknis (Las, Listrik, Bangunan, Otomotif)
     * Rule: Figural >= threshold (60) AND RIASEC contains 'R' -> K1
     */
    public function test_kejuruan_teknis_k1_mumpuni(): void
    {
        $result = Peserta::calculateDiagnosis(50, 75, 'Teknik Las', 'RIA');
        $this->assertStringContainsString('Kuadran 1', $result);
        $this->assertStringContainsString('Kapasitas Mumpuni', $result);
    }

    /**
     * Group: Kejuruan Teknis
     * Rule: Figural < threshold (60) AND RIASEC contains 'R' -> K2
     */
    public function test_kejuruan_teknis_k2_pendampingan(): void
    {
        $result = Peserta::calculateDiagnosis(80, 55, 'Otomotif', 'RSE');
        $this->assertStringContainsString('Kuadran 2', $result);
        $this->assertStringContainsString('Pendampingan', $result);
    }

    /**
     * Group: Kejuruan Teknis
     * Rule: Figural >= threshold (60) AND RIASEC NO 'R' -> K3
     */
    public function test_kejuruan_teknis_k3_eksplorasi(): void
    {
        $result = Peserta::calculateDiagnosis(80, 80, 'Bangunan', 'SEC');
        $this->assertStringContainsString('Kuadran 3', $result);
        $this->assertStringContainsStringIgnoringCase('eksplorasi', $result);
    }

    /**
     * Group: Kejuruan Teknis
     * Rule: Figural < threshold (60) AND RIASEC NO 'R' -> K4
     */
    public function test_kejuruan_teknis_k4_perhatian_khusus(): void
    {
        $result = Peserta::calculateDiagnosis(50, 40, 'Kelistrikan', 'ISA');
        $this->assertStringContainsString('Kuadran 4', $result);
        $this->assertStringContainsString('Perhatian Khusus', $result);
    }

    /**
     * Group: Kejuruan IT/Digital (Web, TIK, Programming, Desain, Grafis)
     * Rule: Numerik >= threshold (60) AND RIASEC contains 'I' or 'A' -> K1
     */
    public function test_kejuruan_it_k1_mumpuni(): void
    {
        $result = Peserta::calculateDiagnosis(85, 40, 'Web Programming', 'EAS');
        $this->assertStringContainsString('Kuadran 1', $result);
    }

    /**
     * Group: Kejuruan IT/Digital
     * Rule: Numerik < threshold (60) AND RIASEC contains 'I' or 'A' -> K2
     */
    public function test_kejuruan_it_k2_pendampingan(): void
    {
        $result = Peserta::calculateDiagnosis(55, 90, 'Desain Grafis', 'ISA');
        $this->assertStringContainsString('Kuadran 2', $result);
    }

    /**
     * Group: Kejuruan IT/Digital
     * Rule: Numerik >= threshold (60) AND RIASEC NO 'I' and NO 'A' -> K3
     */
    public function test_kejuruan_it_k3_eksplorasi(): void
    {
        $result = Peserta::calculateDiagnosis(70, 70, 'TIK', 'RSE');
        $this->assertStringContainsString('Kuadran 3', $result);
    }

    /**
     * Group: Kejuruan IT/Digital
     * Rule: Numerik < threshold (60) AND RIASEC NO 'I' and NO 'A' -> K4
     */
    public function test_kejuruan_it_k4_perhatian_khusus(): void
    {
        $result = Peserta::calculateDiagnosis(40, 80, 'Web Programming', 'REC'); // Wait, REC doesn't have I or A, but wait, REC has R, E, C.
        $this->assertStringContainsString('Kuadran 4', $result);
    }

    /**
     * Group: Kejuruan Default (Unrecognized)
     * Rule: Both Num and Fig < threshold -> K2 (kogAman=false, psiAman=true)
     */
    public function test_kejuruan_default_k2(): void
    {
        $result = Peserta::calculateDiagnosis(50, 50, 'Menjahit', 'RIA');
        $this->assertStringContainsString('Kuadran 2', $result);
    }

    /**
     * Group: Kejuruan Default (Unrecognized)
     * Rule: At least one Num or Fig >= threshold -> K1 (kogAman=true, psiAman=true)
     */
    public function test_kejuruan_default_k1(): void
    {
        $result = Peserta::calculateDiagnosis(50, 65, 'Menjahit', 'RIA');
        $this->assertStringContainsString('Kuadran 1', $result);
    }

    /**
     * Test Dynamic Threshold setting
     */
    public function test_dynamic_threshold_changes_outcome(): void
    {
        // First, confirm it's K1 with threshold 60
        $result1 = Peserta::calculateDiagnosis(65, 80, 'Otomotif', 'RIA');
        $this->assertStringContainsString('Kuadran 1', $result1);

        // Change threshold to 85
        Setting::updateOrCreate(['key' => 'threshold_k2'], ['value' => '85']);

        // Now Figural (80) is below 85, so it should become K2
        $result2 = Peserta::calculateDiagnosis(65, 80, 'Otomotif', 'RIA');
        $this->assertStringContainsString('Kuadran 2', $result2);
    }
}
