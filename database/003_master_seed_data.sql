insert into role (idrole, nama_role) values
    (101, 'Apoteker'),
    (102, 'Admin'),
    (103, 'Kasir'),
    (104, 'Staff Gudang'),
    (105, 'Pelanggan');

insert into user (iduser, username, password, role_idrole) values
    (201, 'rina.wijaya', sha2('Rina@2026', 256), 101),
    (202, 'budi.santoso', sha2('Budi@2026', 256), 102),
    (203, 'sari.dewi', sha2('Sari@2026', 256), 103),
    (204, 'dedi.pratama', sha2('Dedi@2026', 256), 104);

insert into satuan (idsatuan, nama_satuan, status) values
    (301, 'Tablet', 1),
    (302, 'Kapsul', 1),
    (303, 'Strip', 1),
    (304, 'Botol', 1),
    (305, 'Tube', 1),
    (306, 'Dus', 1);

insert into vendor (idvendor, nama_vendor, badan_hukum, status) values
    (401, 'PT Kimia Farma Trading & Distribution', 'P', 'A'),
    (402, 'PT Enseval Putera Megatrading', 'P', 'A'),
    (403, 'CV Anugrah Pharmindo Lestari', 'C', 'A');

insert into margin_penjualan
    (idmargin_penjualan, created_at, persen, status, user_iduser, updated_at) values
    (501, '2026-11-11 08:00:00', 10, 0, 202, '2026-11-11 09:00:00'),
    (502, '2026-11-11 09:00:00', 15, 0, 202, '2026-11-11 10:00:00'),
    (503, '2026-11-11 10:00:00', 20, 1, 202, NULL),
    (504, '2026-11-11 11:00:00', 25.5, 0, 201, '2026-11-11 12:00:00'),
    (505, '2026-11-11 12:00:00', 30, 1, 201, '2026-11-11 13:00:00');

insert into barang
    (idbarang, jenis, nama, satuan_idsatuan, status, harga) values
    (601, 'O', 'Paracetamol 500mg', 303, 1, 5000),
    (602, 'O', 'Amoxicillin 500mg', 303, 1, 12000),
    (603, 'O', 'OBH Combi Batuk Flu 100ml', 304, 1, 15000),
    (604, 'S', 'Vitamin C 1000mg', 306, 1, 25000),
    (605, 'A', 'Masker Medis 3 Ply', 306, 1, 35000),
    (606, 'A', 'Povidone Iodine 60ml', 304, 1, 18000);
