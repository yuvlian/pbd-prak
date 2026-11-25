create or replace view v_satuan as
    select idsatuan, nama_satuan,
           if(status = 1, 'Aktif', 'Nonaktif') as status_teks
    from satuan;

create or replace view v_vendor as
    select idvendor, nama_vendor,
           case badan_hukum
               when 'P' then 'PT'
               when 'C' then 'CV'
               when 'U' then 'UD'
               else badan_hukum
           end as badan_hukum_teks,
           if(status = 'A', 'Aktif', 'Nonaktif') as status_teks
    from vendor;

create or replace view v_margin_penjualan as
    select m.idmargin_penjualan, m.created_at, m.persen,
           if(m.status = 1, 'Aktif', 'Nonaktif') as status_teks,
           m.user_iduser as iduser, u.username, m.updated_at
    from margin_penjualan m
    left join user u on u.iduser = m.user_iduser;

create or replace view v_barang as
    select b.idbarang,
           case b.jenis
               when 'O' then 'Obat'
               when 'S' then 'Suplemen'
               when 'A' then 'Alat Kesehatan'
               else b.jenis
           end as jenis_teks,
           b.nama, b.satuan_idsatuan as idsatuan, s.nama_satuan,
           if(b.status = 1, 'Aktif', 'Nonaktif') as status_teks,
           b.harga
    from barang b
    left join satuan s on s.idsatuan = b.satuan_idsatuan;

create or replace view v_stok_barang as
    select v.*, fn_stok_barang(v.idbarang) as stok
      from v_barang v;

create or replace view v_pengadaan as
    select p.idpengadaan, p.created_at,
           if(p.status = 'S', 'Selesai', 'Tertunda') as status_teks,
           v.nama_vendor,
           u.username,
           p.subtotal_nilai, p.ppn, p.total_nilai
      from pengadaan p
      left join vendor v on v.idvendor = p.vendor_idvendor
      left join user u on u.iduser = p.user_iduser;

create or replace view v_penerimaan as
    select pt.idpenerimaan, pt.created_at,
           if(pt.status = 'S', 'Selesai', 'Tertunda') as status_teks,
           v.nama_vendor,
           u.username,
           coalesce((
               select sum(dpt.sub_total_terima)
                 from detail_penerimaan dpt
                where dpt.penerimaan_idpenerimaan = pt.idpenerimaan
           ), 0) as total_nilai
      from penerimaan pt
      left join pengadaan p on p.idpengadaan = pt.pengadaan_idpengadaan
      left join vendor v on v.idvendor = p.vendor_idvendor
      left join user u on u.iduser = pt.user_iduser;

create or replace view v_penjualan as
    select p.idpenjualan, p.created_at,
           if(p.status = 'S', 'Selesai', 'Tertunda') as status_teks,
           u.username,
           m.persen as margin_persen,
           p.subtotal_nilai, p.ppn, p.total_nilai
      from penjualan p
      left join user u on u.iduser = p.user_iduser
      left join margin_penjualan m
        on m.idmargin_penjualan = p.margin_penjualan_idmargin_penjualan;
