<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MasterDataSeeder extends Seeder
{
    /**
     * Helper to insert or update records while handling SQL Server IDENTITY_INSERT & Identity column update constraints.
     */
    private function seedTable(string $table, string $idColumn, array $records): void
    {
        $driver = DB::connection()->getDriverName();

        foreach ($records as $record) {
            $exists = DB::table($table)->where($idColumn, $record[$idColumn])->exists();

            if ($exists) {
                // Exclude identity column from update payload to prevent SQL Server "Cannot update identity column" error
                $updateData = $record;
                unset($updateData[$idColumn]);

                if (!empty($updateData)) {
                    DB::table($table)->where($idColumn, $record[$idColumn])->update($updateData);
                }
            } else {
                if ($driver === 'sqlsrv') {
                    try {
                        DB::statement("SET IDENTITY_INSERT {$table} ON");
                    } catch (\Throwable $e) {
                        // Ignore
                    }
                }

                DB::table($table)->insert($record);

                if ($driver === 'sqlsrv') {
                    try {
                        DB::statement("SET IDENTITY_INSERT {$table} OFF");
                    } catch (\Throwable $e) {
                        // Ignore
                    }
                }
            }
        }
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. m_ppn
        $this->seedTable('m_ppn', 'id_ppn', [
            [
                'id_ppn' => 2,
                'kode_ppn' => 'PPN-UMUM',
                'nama_ppn' => 'PPN Umum',
                'persen_ppn' => 11.00,
                'effective_from' => '2026-05-10',
                'effective_to' => null,
                'active' => 1,
                'keterangan' => 'Setting awal PPN SPK Gudang PT',
                'modified_by' => null,
                'modified_date' => '2026-05-10 16:11:19.743',
            ],
        ]);

        // 2. master_berat
        $this->seedTable('master_berat', 'id_berat', [
            ['id_berat' => 1, 'nama' => '10', 'created_by' => 265, 'updated_by' => 265, 'created_at' => '2026-07-24 07:30:12.887', 'updated_at' => '2026-07-24 07:30:12.887'],
            ['id_berat' => 2, 'nama' => '5', 'created_by' => 265, 'updated_by' => 265, 'created_at' => '2026-07-24 07:30:16.520', 'updated_at' => '2026-07-24 07:30:16.520'],
        ]);

        // 3. master_karakter
        $this->seedTable('master_karakter', 'id_karakter', [
            ['id_karakter' => 1, 'nama' => 'Kucing', 'created_by' => 265, 'updated_by' => 265, 'created_at' => '2026-07-24 07:29:34.517', 'updated_at' => '2026-07-24 07:29:34.517'],
            ['id_karakter' => 2, 'nama' => 'Beruang', 'created_by' => 265, 'updated_by' => 265, 'created_at' => '2026-07-24 07:29:40.093', 'updated_at' => '2026-07-24 07:29:40.093'],
        ]);

        // 4. master_satuan
        $this->seedTable('master_satuan', 'id_satuan', [
            ['id_satuan' => 1, 'nama' => 'Pcs', 'created_by' => 265, 'updated_by' => 265, 'created_at' => '2026-07-24 07:30:23.090', 'updated_at' => '2026-07-24 07:30:23.090'],
            ['id_satuan' => 2, 'nama' => 'Kg', 'created_by' => 265, 'updated_by' => 265, 'created_at' => '2026-07-24 07:30:34.257', 'updated_at' => '2026-07-24 07:30:34.257'],
        ]);

        // 5. master_tipe
        $this->seedTable('master_tipe', 'id_tipe', [
            ['id_tipe' => 1, 'nama' => 'Freesip', 'created_by' => 265, 'updated_by' => 265, 'created_at' => '2026-07-24 07:30:54.027', 'updated_at' => '2026-07-24 07:31:02.973'],
            ['id_tipe' => 2, 'nama' => 'SM', 'created_by' => 265, 'updated_by' => 265, 'created_at' => '2026-07-24 07:31:10.363', 'updated_at' => '2026-07-24 07:31:10.363'],
        ]);

        // 6. master_ukuran
        $this->seedTable('master_ukuran', 'id_ukuran', [
            ['id_ukuran' => 1, 'nama' => '500', 'created_by' => 265, 'updated_by' => 265, 'created_at' => '2026-07-24 07:29:50.397', 'updated_at' => '2026-07-24 07:29:50.397'],
            ['id_ukuran' => 2, 'nama' => '300', 'created_by' => 265, 'updated_by' => 265, 'created_at' => '2026-07-24 07:29:55.530', 'updated_at' => '2026-07-24 07:29:55.530'],
        ]);

        // 7. master_uom
        $this->seedTable('master_uom', 'id_uom', [
            ['id_uom' => 3, 'nama_uom' => 'Pcs', 'deskripsi' => 'pcs', 'created_by' => 265, 'updated_by' => 265, 'created_at' => '2026-08-14 07:27:23.493', 'updated_at' => '2026-08-14 07:27:23.493'],
        ]);

        // 8. master_warna
        $this->seedTable('master_warna', 'id_warna', [
            ['id_warna' => 1, 'nama' => 'Merah', 'created_by' => 265, 'updated_by' => 265, 'created_at' => '2026-07-24 07:29:10.953', 'updated_at' => '2026-07-24 07:29:10.953'],
            ['id_warna' => 2, 'nama' => 'Kuning', 'created_by' => 265, 'updated_by' => 265, 'created_at' => '2026-07-24 07:29:15.687', 'updated_at' => '2026-07-24 07:29:15.687'],
            ['id_warna' => 3, 'nama' => 'Hijau', 'created_by' => 265, 'updated_by' => 265, 'created_at' => '2026-07-24 07:29:24.217', 'updated_at' => '2026-07-24 07:29:24.217'],
        ]);

        // List of 40 Dummy Items to seed in t_barang, master_produk, and master_produk_detail
        $dummyCatalog = [
            ['sku' => 'PRD2026001', 'nama' => 'Tumbler Vacuum Stainless Steel Premium 500ml', 'harga' => 75000, 'cat_id' => '1'],
            ['sku' => 'PRD2026002', 'nama' => 'Botol Minum Anak Karakter Anti Bocor 350ml', 'harga' => 45000, 'cat_id' => '1'],
            ['sku' => 'PRD2026003', 'nama' => 'Mug Kopi Thermal Double Wall Stainless Steel', 'harga' => 60000, 'cat_id' => '1'],
            ['sku' => 'PRD2026004', 'nama' => 'Kotak Makan Lunch Box Stainless Steel 3 Sekat', 'harga' => 85000, 'cat_id' => '2'],
            ['sku' => 'PRD2026005', 'nama' => 'Botol Olahraga Aluminium Sport Water Bottle 750ml', 'harga' => 55000, 'cat_id' => '1'],
            ['sku' => 'PRD2026006', 'nama' => 'Termos Air Panas Portable LED Display Suhu 500ml', 'harga' => 95000, 'cat_id' => '1'],
            ['sku' => 'PRD2026007', 'nama' => 'Gelas Kaca Thermal Borosilikat dengan Cover Silicone', 'harga' => 40000, 'cat_id' => '1'],
            ['sku' => 'PRD2026008', 'nama' => 'Sendok Garpu Set Travel Kit Stainless Steel', 'harga' => 35000, 'cat_id' => '2'],
            ['sku' => 'PRD2026009', 'nama' => 'Wadah Makanan Mangkuk Stainless Steel Penutup Vacuum', 'harga' => 65000, 'cat_id' => '2'],
            ['sku' => 'PRD2026010', 'nama' => 'Sedotan Stainless Steel Reusable Eco-Friendly Set', 'harga' => 25000, 'cat_id' => '2'],
            ['sku' => 'PRD2026011', 'nama' => 'Tas Bekal Insulasi Thermal Cooler Bag Portable', 'harga' => 50000, 'cat_id' => '2'],
            ['sku' => 'PRD2026012', 'nama' => 'Botol Infused Water TRITAN BPA Free 800ml', 'harga' => 70000, 'cat_id' => '1'],
            ['sku' => 'PRD2026013', 'nama' => 'Tumbler Kaca Estetik dengan Sedotan Silicone 450ml', 'harga' => 48000, 'cat_id' => '1'],
            ['sku' => 'PRD2026014', 'nama' => 'Termos Mini Pocket Size Stainless Steel 200ml', 'harga' => 65000, 'cat_id' => '1'],
            ['sku' => 'PRD2026015', 'nama' => 'Botol Minum Shaker Gym Fitness Mixer 600ml', 'harga' => 42000, 'cat_id' => '1'],
            ['sku' => 'PRD2026016', 'nama' => 'Wadah Sup Thermal Soup Jar Stainless Steel 600ml', 'harga' => 110000, 'cat_id' => '2'],
            ['sku' => 'PRD2026017', 'nama' => 'Teko Termos Air Panas Jug Stainless Steel 1.5 Liter', 'harga' => 145000, 'cat_id' => '1'],
            ['sku' => 'PRD2026018', 'nama' => 'Cangkir Enamel Vintage Motif Retro 400ml', 'harga' => 30000, 'cat_id' => '1'],
            ['sku' => 'PRD2026019', 'nama' => 'Botol Spray Disinfektan Aluminium Portable 100ml', 'harga' => 20000, 'cat_id' => '3'],
            ['sku' => 'PRD2026020', 'nama' => 'Tempat Bumbu Dapur Stainless Steel Magnetik Set', 'harga' => 90000, 'cat_id' => '2'],
            ['sku' => 'PRD2026021', 'nama' => 'Pulpen Gel Ergonomis Black Ink 0.5mm Pack 12 Pcs', 'harga' => 36000, 'cat_id' => '4'],
            ['sku' => 'PRD2026022', 'nama' => 'Kertas HVS A4 80gsm 1 Rim High White', 'harga' => 52000, 'cat_id' => '4'],
            ['sku' => 'PRD2026023', 'nama' => 'Map Binder A4 Leather Cover Business Edition', 'harga' => 78000, 'cat_id' => '4'],
            ['sku' => 'PRD2026024', 'nama' => 'Calculator Desk Financial 12 Digit Solar Power', 'harga' => 120000, 'cat_id' => '4'],
            ['sku' => 'PRD2026025', 'nama' => 'Stapler Heavy Duty 100 Sheets Office Desk', 'harga' => 160000, 'cat_id' => '4'],
            ['sku' => 'PRD2026026', 'nama' => 'Whiteboard Eraser Magnetic + 4 Color Marker Set', 'harga' => 45000, 'cat_id' => '4'],
            ['sku' => 'PRD2026027', 'nama' => 'Seragam Kerja Poloshirt Cotton Combed Navy L', 'harga' => 95000, 'cat_id' => '5'],
            ['sku' => 'PRD2026028', 'nama' => 'Celemek Apron Chef Waterproof Canvas Adjustable', 'harga' => 68000, 'cat_id' => '5'],
            ['sku' => 'PRD2026029', 'nama' => 'Hand Sanitizer Gel 500ml Pump Bottle Alcohol 75%', 'harga' => 38000, 'cat_id' => '3'],
            ['sku' => 'PRD2026030', 'nama' => 'Lap Microfiber High Density Cleaning Cloth 40x40', 'harga' => 18000, 'cat_id' => '3'],
            ['sku' => 'PRD2026031', 'nama' => 'Disinfectan Spray Multipurpose Surface Cleaner 1L', 'harga' => 55000, 'cat_id' => '3'],
            ['sku' => 'PRD2026032', 'nama' => 'Keset Karet Anti Slip Heavy Duty Entrance Mat', 'harga' => 115000, 'cat_id' => '3'],
            ['sku' => 'PRD2026033', 'nama' => 'Tempat Sampah Pedal Stainless Steel 12 Liter', 'harga' => 135000, 'cat_id' => '3'],
            ['sku' => 'PRD2026034', 'nama' => 'Lampu Meja LED Desk Lamp Touch Sensor Dimmer', 'harga' => 125000, 'cat_id' => '6'],
            ['sku' => 'PRD2026035', 'nama' => 'Extension Cable Stopkontak 5 Lubang Switch 3 Meter', 'harga' => 85000, 'cat_id' => '6'],
            ['sku' => 'PRD2026036', 'nama' => 'Mouse Wireless Silent Click Optical 1600 DPI', 'harga' => 75000, 'cat_id' => '6'],
            ['sku' => 'PRD2026037', 'nama' => 'Keyboard Mechanical Ergonomic USB Cable', 'harga' => 210000, 'cat_id' => '6'],
            ['sku' => 'PRD2026038', 'nama' => 'Stand Laptop Ergonomic Aluminium Cooling Pad', 'harga' => 140000, 'cat_id' => '6'],
            ['sku' => 'PRD2026039', 'nama' => 'Cable Management Box Organizer Cable Cord Neat', 'harga' => 48000, 'cat_id' => '6'],
            ['sku' => 'PRD2026040', 'nama' => 'Timbangan Digital Dapur Scale Stainless Precision', 'harga' => 88000, 'cat_id' => '2'],
        ];

        // Seed t_barang records using seedTable helper to handle IDENTITY_INSERT t_barang ON/OFF
        $barangsToSeed = [];
        foreach ($dummyCatalog as $index => $item) {
            $barangsToSeed[] = [
                'id_barang' => 4000 + $index,
                'kode_barang' => $item['sku'],
                'nama_barang' => $item['nama'],
                'harga_beli' => $item['harga'] * 0.75,
                'harga_beli_before' => 0,
                'harga_jual' => $item['harga'],
                'harga_jual_before' => 0,
                'harga_jual_jumbo' => $item['harga'] * 1.2,
                'harga_jual_jumbo_before' => 0,
                'margin' => $item['harga'] * 0.25,
                'satuan' => 'Pcs',
                'stok' => 100 + ($index * 5),
                'qty_pos' => 0,
                'min_stok' => 10,
                'max_stok' => 500,
                'category_code' => $item['cat_id'],
                'min_vendor' => 0,
                'min_cabang' => 0,
                'kirim_langsung' => 0,
                'active' => 1,
                'modified_by' => 265,
                'modified_date' => '2026-08-17 10:00:00',
            ];
        }
        $this->seedTable('t_barang', 'id_barang', $barangsToSeed);

        // Seed master_produk records
        $produksToSeed = [];
        foreach ($dummyCatalog as $index => $item) {
            $produksToSeed[] = [
                'id_produk' => 15 + $index,
                'nama_produk' => $item['nama'],
                'deskripsi' => 'Deskripsi lengkap ' . $item['nama'],
                'created_by' => 265,
                'updated_by' => 265,
                'created_at' => '2026-08-17 10:00:00',
                'updated_at' => '2026-08-17 10:00:00',
            ];
        }
        $this->seedTable('master_produk', 'id_produk', $produksToSeed);

        // Seed master_produk_detail records
        $detailsToSeed = [];
        foreach ($dummyCatalog as $index => $item) {
            $detailsToSeed[] = [
                'id_produk_detail' => 16 + $index,
                'id_produk' => 15 + $index,
                'kode_barang' => $item['sku'],
                'id_tipe' => ($index % 2) + 1,
                'id_satuan' => ($index % 2) + 1,
                'id_berat' => ($index % 2) + 1,
                'id_ukuran' => ($index % 2) + 1,
                'id_warna' => ($index % 3) + 1,
                'id_karakter' => ($index % 2) + 1,
                'id_uom' => 3,
                'created_by' => 265,
                'updated_by' => 265,
                'created_at' => '2026-08-17 10:00:00',
                'updated_at' => '2026-08-17 10:00:00',
                'category_id' => $item['cat_id'],
            ];
        }
        $this->seedTable('master_produk_detail', 'id_produk_detail', $detailsToSeed);

        Schema::enableForeignKeyConstraints();
    }
}
