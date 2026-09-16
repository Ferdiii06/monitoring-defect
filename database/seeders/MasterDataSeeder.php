<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CarType;
use App\Models\Carline;
use App\Models\DefectType;
use App\Models\SubDefectType;
use App\Models\InspectProcessType;
use App\Models\FinalAssyInspectType;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Inspect Process Types
        $inspectProcesses = [
            'Cutting', 'Manual Crimping', 'Joint Crimping', 'Bonder', 
            'Heatshrink', 'Reychern', 'Solder', 'La Term. WP', 'A/B', 
            'Jam Twist', 'WP', 'MN20', 'EJ30', 'MN10', 'TP25', 'EJ35'
        ];
        foreach ($inspectProcesses as $proc) {
            InspectProcessType::firstOrCreate(['name' => $proc]);
        }

        // 2. Car Types & Carlines
        $conveyorMap = [
            'TOYOTA' => [
                "664W-C5", "664W-C5C", "664W-C5A", "664W-C5B", "664W-C5D", "711W TNGA-C5", "711W TNGA-C5A", "737W TNGA-C5A", "737W TNGA-C5",
                "738W-C5C", "858W-C5C", "810W-C5", "941W-C5", "023J-C5", "072Y-C5", "718W-AB5.HEV", "718W-C4.CONV", "718W-C4.TNGA", "891W/892W-C1.GAS LHD",
                "853W-AT2.HEV LHD", "853W-AT6.GAS LHD", "853W-AT16.GAS LHD", "852W-AT19.HEV PHV LHD", "852W-AT2.HEV PHV LHD", "852W-AT19.HEV PHV RHD",
                "852W-AT6.GAS LHD", "909W-AT7.GAS LHD", "909W-AT11.HEV LHD", "909W-AT9.GAS LHD", "910W-AT7.GAS LHD", "910W-AT11.HEV LHD",
                "910W-AT9.GAS LHD", "953W-C6.HEV RHD", "953W-C6.HEV LHD", "953W ENG NO.3-C9", "898W-AB5.HEV", "898W-C4.CONV", "898W-C4.TNGA"
            ],
            'NISSAN' => [
                "P33A-B1.BAT", "P33A-B1.CELL", "J32V-B2.LHD", "J32V-B2.RHD", "J42U-B3.EGI", "J42U-B3.ENGINE", "J42U-B2.DOOR RH", "J42U-B2.DOOR LH", "P33C-B1.BAT", "P33C-B1.CELL"
            ],
            'MAZDA' => [
                "J72A-12B.LHD", "J72A-AB9.RHD", "J72A-16C.LHD", "J72K-16C.LHD", "J30A-AB6.EXTEND LHD", "J30A-AB1.INPANEL LHD", "J30A-AB6.EXTEND RHD", "J30A-AB1.INPANEL RHD", "J69P-AB8.EXTEND LHD", "J69P-AB8.INPANEL LHD", "J69P-AB8.EXTEND RHD", "J69P-AB8.INPANEL RHD", "J69P-AB9.EXTEND LHD", "J69P-AB3.INPANEL LHD"
            ]
        ];

        foreach ($conveyorMap as $carName => $lines) {
            $carType = CarType::firstOrCreate(['name' => $carName]);
            foreach ($lines as $lineName) {
                Carline::firstOrCreate([
                    'car_type_id' => $carType->id,
                    'name' => $lineName,
                ]);
            }
        }

        // 3. Final Assy Defect Types & Sub Defect Types
        $finalAssyDefects = [
            'INSER CIRCUIT' => ['1.A - CROSS CIRCUIT', '1.B - CIRCUIT NOT INSERT', '1.C - WRONG INSERT CIRCUIT', '1.D - WRONG CAVITY', '1.E - MISSING CIRCUIT', '1.F - TPO'],
            'DAMAGE/DEFORM/BROKEN PART' => ['2.A - DAMAGE CLIP', '2.B - DAMAGE CONNECTOR', '2.C - DAMAGE GROMMET', '2.D - DAMAGE / SCRATCH INSULATION', '2.E - DAMAGE PROTECTOR', '2.F - DAMAGE SPACER', '2.G - DAMAGE TUBE', '2.H - DAMAGE BOLT / TORQUE', '2.I - DAMAGE R/B', '2.J - DAMAGE FUSE', '2.K - DAMAGE RELAY ', '2.L - DAMAGE N/P', '2.M - DAMAGE COVER', '2.N - DAMAGE SEAL RUBBER', '2.O - DAMAGE BRACKET CONNECTOR', '2.P - DAMAGE WASHER HOSE','2.Q - CUT WIRE', '2.R - DAMAGE USB', '2.S - BENT TERMINAL','2.T - DEFORM TERMINAL','2.U - BROKEN TERMINAL', '2.V - FLARE TERMINAL'],
            'MISSING PART' => ['3.A - MISSING CLIP', '3.B - MISSING COVER', '3.C - MISSING GREASE', '3.D - MISSING GROMMET', '3.E - MISSING PROTECTOR', '3.F - MISSING SEAL RUBBER', '3.G - MISSING SPACER', '3.H - MISSING SPOT TAPE', '3.I - MISSING FOAM TAPE', '3.J - MISSING TIE BACK', '3.K - MISSING TUBE', '3.L - MISSING JC / BUSSBAR', '3.M - MISSING PULLER', '3.N - MISSING PLUG', '3.O - MISSING FUSE', '3.P - MISSING RELAY', '3.Q - MISSING N/P', '3.R - MISSING MARKING / STAMP N/P', '3.S - MISSING SOLDER', '3.T - MISSING USB CABLE', '3.U - BRACKET CONNECTOR ', '3.V - WASHER HOSE'],
            'DIMENSON DEFECT' => ['4.A - DIMENSION BRANCH', '4.B - DIMENSION TRUNK', '4.C - DIMENSION CLIP', '4.D - DIMENSION PROTECTOR', '4.E - DIMENSION GROMMET', '4.F - DIMENSION TUBE', '4.G - DIM.Y'],
            'HALF LOCK / INCOMPLETE DOCKING' => ['5.A - HALF LOCK SPACER / RETAINER', '5.B - MISALIGN', '5.C - HALF LOCK DOCKING J/C', '5.D - HALF LOCK DOCKING LA TERMINAL', '5.E - HALF LOCK COVER R/B', '5.F - HALF LOCK PROTECTOR', '5.G - HALF LOCK INSERT FUSE', '5.H - HALF LOCK INSERT RELAY', '5.I - LOOSE TORQUE'],
            'WRONG PART' => ['6.A - CRACK', '6.B - MISALIGN', '6.C - WRONG CIRCUIT', '6.D - WRONG CLIP', '6.E - WRONG COVER', '6.F - WRONG TAPE', '6.G - WRONG GROMMET', '6.H - WRONG PROTECTOR', '6.I - WRONG SEAL RUBBER', '6.J - WRONG SPACER / HOLDER', '6.K - WRONG FOAM TAPE', '6.L - WRONG TUBE', '6.M - WRONG JC / BUSSBAR', '6.N - WRONG PLUG','6.O - WRONG FUSE','6.P - WRONG RELAY','6.Q - WRONG N/P'],
            'TAPING DEFECT' => ['7.A - WRONG TAPING METHOD', '7.B - MISSING TAPING', '7.C - WRONG SPOT TAPE', '7.D - WRONG TIE BACK', '7.E - TAPING BENDERA'],
            'WRONG ORIENTATION PART' => ['8.A - ORIENTASI CLIP', '8.B - ORIENTASI BRANCH', '8.C - ORIENTASI GROMMET', '8.D - ORIENTASI COVER CONN.', '8.E - ORIENTASI N/P', '8.F - ORIENTASI TIE BACK' ],
            'CUTTING - CRIMPING PRE ASSY DEFECT' => ['9.A - SALAH BENTUK REAR CRIMPING', '9.B - BUTHYL MELELEH', '9.C - OVER MELT SHRINK TUBE', '9.D - SOLDER N-OK', '9.E - RAYCHAM N-OK', '9.F - BONDER LEPAS', '9.G - OVER CIRCUIT BONDER', '9.H - MISSING CIRCUIT BONDER', '9.I - SALAH CIRCUIT BONDER', '9.J - SALAH KIND WIRE ', '9.K - SALAH SIZE WIRE', '9.L - INSULATION MUNDUR', '9.M - SEAL RUBBER MUNDUR', '9.N - FRAYING CORE', '9.O - CRACK TERMINAL' ],
            'INJECTION GROMMET / SISUI DEFECT' => ['10.A - INJECTION GROMMET BERGELEMBUNG', '10.B - INJECTION GROMMET KURANG', '10.C - INJECTION GROMMET TDK MATANG', '10.D - SISUI BOCOR'],
            'LAIN-LAIN' => ['11.A - FOREIGN MATERIAL', '11.B - CIRCUIT TERJEPIT', '11.C - AIR CHECKER N-OK', '11.D - BAND CLIP KEPENDEKAN', '11.E - BAND CLIP PANJANG'],
        ];

        foreach ($finalAssyDefects as $defName => $subList) {
            $defectType = DefectType::firstOrCreate([
                'name' => $defName,
                'type' => 'Final Assy',
            ]);
            foreach ($subList as $subName) {
                SubDefectType::firstOrCreate([
                    'defect_type_id' => $defectType->id,
                    'name' => $subName,
                ]);
            }
        }

        // 4. Pre Assy Defect Types & Sub Defect Types
        $preAssyDefects = [
            'CORE' => ['A.1 - FRAYING', 'A.2 - CUT CORE', 'A.3 - TIDAK TERATUR', 'A.4 - MAJU','A.5 - MUNDUR', 'A.6 - TIDAK TERCRIMPING', 'A.7 - SCRATCH'],
            'TERMINAL' => ['B.1 - TERGORES', 'B.2 - BENT UP','B.3 - BENT DOWN', 'B.4 - MELINTIR', 'B.5 - UJUNG TERPOTONG', 'B.6 - OPEN/FLARE', 'B.7 - DEFORM', 'B.8 - BRIDGE TERLALU PANJANG', 'B.9 - CANTILEVER RUSAK', 'B.10 - LEPAS DARI CIRCUIT'],
            'FRONT CRIMPING' => ['C.1 - C/H TERLALU TINGGI', 'C.2 - C/H TERLALU RENDAH','C.3 - C/W TERLALU TINGGI', 'C.4 - C/W TERLALU RENDAH', 'C.5 - FLASH'],
            'REAR CRIMPING' => ['D.1 - C/H - TERLALU TINGGI', 'D.2 - C/H TERLALU RENDAH', 'D.3 - C/W TERLALU TINGGI', 'D.4 - C/W TERLALU RENDAH', 'D.5 - ADA DI DALAM INSULASI', 'D.6 - TIDAK SEIMBANG'],
            'INSULATION' => ['E.1 - TERCRIMPING', 'E.2 - TERLALU MUNDUR', 'E.3 - DAMAGE', 'E.4 - TIDAK RATA'],
            'SEAL SUMBER' => ['F.1 - TERPOTONG', 'F.2 - TERBALIK', 'F.3 - TERLALU MUNDUR', 'F.4 - TERLALU MAJU', 'F.5 - TERCRIMPING', 'F.6 - MISSING', 'F.7 - SEAL SOBEK'],
            'CRIMPING' => ['G.1 - FOREIGN MATERIAL', 'G.2 - ADB.1 TERMMINAL TERCIMPING', 'G.3 - NO CORE', 'G.4 - NO STRIPPING'],
            'LAIN-LAIN' => ['H.1 - LANCE RUSAK', 'H.2 - STABILIZER RUSAK', 'H.3 - BELLMOUTH TIDAK STANDART', 'H.4 - KONDISI CORE BAG.A', 'H.5 - RESIN MASUK BAG.A', 'H.6 - RESIN BAREL BAG.B TERBUKA', 'H.7 - CORE TERLIHAT ATAS SISI C', 'H.8 - CORE TERLIHAT SAMPING SISI C', 'H.9 - SISI PUNGGUNG', 'H.10 - ABNORMAL RESIN', 'H.11 - PANJANG WELDING N-OK', 'H.12 - CIRCUIT TIDAK TERBONDER', 'H.13 - BONDER RETAK', 'H.14 - STRIPPING KEPANJANGAN'],
        ];

        foreach ($preAssyDefects as $defName => $subList) {
            $defectType = DefectType::firstOrCreate([
                'name' => $defName,
                'type' => 'Pre Assy',
            ]);
            foreach ($subList as $subName) {
                SubDefectType::firstOrCreate([
                    'defect_type_id' => $defectType->id,
                    'name' => $subName,
                ]);
            }
        }

        // 5. Final Assy Inspect Types
        $finalAssyInspectTypes = [
            '67120 - AB6 EXTEND LHD',
            '67240 - AB6 EXTEND LHD',
            '67550 - AB6 EXTEND LHD',
            '67120 - AB6 EXTEND RHD',
            '67240 - AB6 EXTEND RHD',
            '67550 - AB6 EXTEND RHD',
            '67120 - AB9 EXTEND LHD',
            '67240 - AB9 EXTEND LHD',
        ];

        foreach ($finalAssyInspectTypes as $name) {
            FinalAssyInspectType::firstOrCreate(['name' => $name]);
        }
    }
}
