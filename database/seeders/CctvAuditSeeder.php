<?php

namespace Database\Seeders;

use App\Models\CommandCenterCamera;
use App\Models\Station;
use Illuminate\Database\Seeder;

/**
 * Loads the 15 provincial offices from the "CCTV เพิ่มเติม" audit CSV as
 * real CommandCenterCamera records -- one row per camera where a count was
 * given, one representative row where it wasn't. Every row is honestly
 * marked `integration_readiness = not_ready` (or `pending_review` for the
 * three sites with an active blocker) because the source data contains only
 * a cloud-app name and a login, never an IP/RTSP/ONVIF endpoint -- see the
 * CCTV Integration Feasibility Assessment. `manufacturer` is a best-guess
 * inferred from the app name, not a confirmed fact, and is labelled as such
 * in `readiness_notes`.
 *
 * This does not make any camera stream. It makes the system reflect what is
 * actually known about each real office, instead of showing only the
 * unrelated Chiang Mai/Lampang demo cameras seeded by CommandCenterSeeder.
 */
class CctvAuditSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->offices() as $office) {
            $station = Station::where('name_th', 'like', "%{$office['province_th']}%")->first();

            if (! $station) {
                $this->command?->warn("Station not found for {$office['province_th']} — skipping.");
                continue;
            }

            $cameraCount = $office['camera_count'] ?? 1;
            // Disambiguate stations that appear twice in the source (e.g. Kamphaeng
            // Phet has two separate installations) by including the app name --
            // otherwise the second row's updateOrCreate would silently overwrite
            // the first instead of creating a second record.
            $appTag = $office['cloud_app'] ? " [{$office['cloud_app']}]" : '';

            for ($i = 1; $i <= $cameraCount; $i++) {
                $label = $cameraCount > 1 ? "Camera {$i}" : 'Camera';

                CommandCenterCamera::updateOrCreate(
                    [
                        'station_id' => $station->id,
                        'name' => "{$station->name_en} — {$label}{$appTag} (CCTV Audit)",
                    ],
                    [
                        'location' => $station->name_en,
                        'manufacturer' => $office['manufacturer'],
                        'cloud_app' => $office['cloud_app'],
                        'status' => 'offline', // Command Center has no live visibility yet -- see readiness_notes
                        'integration_readiness' => $office['readiness'],
                        'readiness_notes' => $office['notes'],
                        'credentials_encrypted' => $office['username'] || $office['password']
                            ? json_encode(['username' => $office['username'], 'password' => $office['password']])
                            : null,
                    ]
                );
            }
        }

        $this->command?->info('CCTV audit data loaded as camera records (all not_ready / pending_review — see readiness_notes).');
    }

    /**
     * @return array<int, array{
     *   province_th:string, cloud_app:?string, manufacturer:?string,
     *   username:?string, password:?string, camera_count:?int,
     *   readiness:string, notes:string
     * }>
     */
    private function offices(): array
    {
        $notReady = CommandCenterCamera::READINESS_NOT_READY;
        $pendingReview = CommandCenterCamera::READINESS_PENDING_REVIEW;

        return [
            [
                'province_th' => 'พะเยา', 'cloud_app' => 'DMSS', 'manufacturer' => 'Dahua (inferred from app, unconfirmed)',
                'username' => null, 'password' => null, 'camera_count' => 4,
                'readiness' => $notReady,
                'notes' => 'Source: CCTV audit CSV. Only a cloud-app name is on file — no IP, RTSP, or ONVIF endpoint. 4 cameras per field note.',
            ],
            [
                'province_th' => 'สุโขทัย', 'cloud_app' => 'EZView', 'manufacturer' => 'Uniview (inferred from app, unconfirmed)',
                'username' => '+66 62-828-1829', 'password' => 'Sukhothai.imm', 'camera_count' => 1,
                'readiness' => $notReady,
                'notes' => 'Source: CCTV audit CSV. An installation-method document was referenced but not attached. No IP/RTSP/ONVIF endpoint on file.',
            ],
            [
                'province_th' => 'ลำปาง', 'cloud_app' => 'Smart pss', 'manufacturer' => 'Dahua (SmartPSS is Dahua\'s own desktop VMS)',
                'username' => null, 'password' => 'immlp400123', 'camera_count' => 1,
                'readiness' => $notReady,
                'notes' => 'Source: CCTV audit CSV. Field note references a linked recovery email (partially redacted: int****@gmail.com). No IP/RTSP/ONVIF endpoint on file.',
            ],
            [
                'province_th' => 'เชียงราย', 'cloud_app' => 'DMSS', 'manufacturer' => 'Dahua (inferred from app, unconfirmed)',
                'username' => null, 'password' => null, 'camera_count' => 2,
                'readiness' => $notReady,
                'notes' => 'Source: CCTV audit CSV. Source data had two ambiguous, unlabeled values in the password field ("admin123456" and "crimm2568=/border") — stored as neither is confirmed correct. Ask the installer which applies. No IP/RTSP/ONVIF endpoint on file.',
            ],
            [
                'province_th' => 'ตาก', 'cloud_app' => null, 'manufacturer' => null,
                'username' => 'Police', 'password' => 'Tak12345', 'camera_count' => 1,
                'readiness' => $notReady,
                'notes' => 'Source: CCTV audit CSV. No app/platform was even named — a site visit is needed before anything else can be determined.',
            ],
            [
                'province_th' => 'เพชรบูรณ์', 'cloud_app' => null, 'manufacturer' => null,
                'username' => null, 'password' => null, 'camera_count' => 1,
                'readiness' => $notReady,
                'notes' => 'Source: CCTV audit CSV. Only note on file: "menu for installing additional cameras" — no system identified at all.',
            ],
            [
                'province_th' => 'อุทัยธานี', 'cloud_app' => 'DMSS', 'manufacturer' => 'Dahua (inferred from app, unconfirmed)',
                'username' => 'uthaithani.imm@gmail.com', 'password' => 'uthai@789', 'camera_count' => 1,
                'readiness' => $notReady,
                'notes' => 'Source: CCTV audit CSV. No IP/RTSP/ONVIF endpoint on file.',
            ],
            [
                'province_th' => 'อุตรดิตถ์', 'cloud_app' => 'SuperLive Plus', 'manufacturer' => 'Hikvision-compatible OEM (inferred from app, unconfirmed)',
                'username' => 'admin', 'password' => '123456', 'camera_count' => 1,
                'readiness' => $notReady,
                'notes' => 'Source: CCTV audit CSV. Login on file is still the factory default — flag to IT/installer regardless of integration timeline. No IP/RTSP/ONVIF endpoint on file.',
            ],
            [
                'province_th' => 'กำแพงเพชร', 'cloud_app' => 'IoT Smart Home, WATASHI IoT', 'manufacturer' => 'Generic consumer ODM (unconfirmed)',
                'username' => 'game.nattaphon66', 'password' => null, 'camera_count' => 1,
                'readiness' => $pendingReview,
                'notes' => 'Source: CCTV audit CSV. BLOCKED: field note says the account activation link no longer works. Closed consumer cloud ecosystem — rarely exposes ONVIF/RTSP even when functioning. Needs a decision on whether to pursue this site or treat it as a hardware replacement candidate.',
            ],
            [
                'province_th' => 'แพร่', 'cloud_app' => null, 'manufacturer' => null,
                'username' => null, 'password' => null, 'camera_count' => 1,
                'readiness' => $notReady,
                'notes' => 'Source: CCTV audit CSV. Only note on file: "menu for installing additional cameras" — no system identified at all.',
            ],
            [
                'province_th' => 'พิจิตร', 'cloud_app' => null, 'manufacturer' => null,
                'username' => null, 'password' => null, 'camera_count' => 1,
                'readiness' => $pendingReview,
                'notes' => 'Source: CCTV audit CSV. BLOCKED: field note says the existing viewer account is at its user limit and cannot add another user. Needs the vendor to raise the limit, or a move to local network access, before anything else is possible.',
            ],
            [
                'province_th' => 'ลำพูน', 'cloud_app' => null, 'manufacturer' => null,
                'username' => null, 'password' => null, 'camera_count' => 1,
                'readiness' => $pendingReview,
                'notes' => 'Source: CCTV audit CSV. BLOCKED: field note says the installing vendor is currently abroad. No reachable technical contact — an alternate contact needs to be found first.',
            ],
            [
                'province_th' => 'พิษณุโลก', 'cloud_app' => 'SuperLive Plus', 'manufacturer' => 'Hikvision-compatible OEM (inferred from app, unconfirmed)',
                'username' => null, 'password' => null, 'camera_count' => 1,
                'readiness' => $notReady,
                'notes' => 'Source: CCTV audit CSV. No credentials or further detail on file beyond the app name.',
            ],
            [
                'province_th' => 'แม่ฮ่องสอน', 'cloud_app' => 'Reco', 'manufacturer' => null,
                'username' => null, 'password' => null, 'camera_count' => 1,
                'readiness' => $notReady,
                'notes' => 'Source: CCTV audit CSV. No credentials, manufacturer, or further detail of any kind on file.',
            ],
            [
                'province_th' => 'กำแพงเพชร', 'cloud_app' => 'Hiview', 'manufacturer' => 'Unconfirmed OEM (Hiview is used by multiple vendors)',
                'username' => 'imm5', 'password' => 'Immigration5', 'camera_count' => 1,
                'readiness' => $notReady,
                'notes' => 'Source: CCTV audit CSV, second entry for this office (a separate installation from the IoT Smart Home/WATASHI IoT row). No IP/RTSP/ONVIF endpoint on file.',
            ],
        ];
    }
}
