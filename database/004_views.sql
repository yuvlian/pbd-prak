create or replace view v_role as
    select idrole, nama_role
    from role;

create or replace view v_satuan as
    select idsatuan, nama_satuan, status,
           if(status = 1, 'Aktif', 'Nonaktif') as status_teks
    from satuan;

create or replace view v_vendor as
    select idvendor, nama_vendor, badan_hukum,
           case badan_hukum
               when 'P' then 'PT'
               when 'C' then 'CV'
               when 'U' then 'UD'
               else badan_hukum
           end as badan_hukum_teks,
           status,
           if(status = 'A', 'Aktif', 'Nonaktif') as status_teks
    from vendor;

create or replace view v_user as
    select u.iduser, u.username, u.idrole, r.nama_role
    from user u
    left join role r on r.idrole = u.idrole;

create or replace view v_margin_penjualan as
    select m.idmargin_penjualan, m.created_at, m.persen, m.status,
           if(m.status = 1, 'Aktif', 'Nonaktif') as status_teks,
           m.iduser, u.username, m.updated_at
    from margin_penjualan m
    left join user u on u.iduser = m.iduser;

create or replace view v_barang as
    select b.idbarang, b.jenis,
           case b.jenis
               when 'O' then 'Obat'
               when 'S' then 'Suplemen'
               when 'A' then 'Alat Kesehatan'
               else b.jenis
           end as jenis_teks,
           b.nama, b.idsatuan, s.nama_satuan, b.status,
           if(b.status = 1, 'Aktif', 'Nonaktif') as status_teks,
           b.harga
    from barang b
    left join satuan s on s.idsatuan = b.idsatuan;
