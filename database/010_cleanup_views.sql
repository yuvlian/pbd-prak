create or replace view v_pengadaan as
    select p.idpengadaan, p.created_at, p.status,
           if(p.status = 'S', 'Selesai', 'Tertunda') as status_teks,
           v.nama_vendor,
           u.username,
           p.subtotal_nilai, p.ppn, p.total_nilai
      from pengadaan p
      left join vendor v on v.idvendor = p.vendor_idvendor
      left join user u on u.iduser = p.user_iduser;

create or replace view v_detail_pengadaan as
    select dp.pengadaan_idpengadaan, dp.barang_idbarang, b.nama,
           dp.harga_satuan, dp.jumlah, dp.sub_total
      from detail_pengadaan dp
      left join barang b on b.idbarang = dp.barang_idbarang;

create or replace view v_sisa_pengadaan as
    select p.idpengadaan, p.created_at, v.nama_vendor,
           b.nama, dp.jumlah,
           fn_sisa_pesanan(p.idpengadaan, dp.barang_idbarang) as sisa
      from pengadaan p
      join vendor v on v.idvendor = p.vendor_idvendor
      join detail_pengadaan dp on dp.pengadaan_idpengadaan = p.idpengadaan
      join barang b on b.idbarang = dp.barang_idbarang;

create or replace view v_penerimaan as
    select pt.idpenerimaan, pt.created_at, pt.status,
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

create or replace view v_detail_penerimaan as
    select dpt.penerimaan_idpenerimaan,
           dpt.barang_idbarang, b.nama,
           dpt.jumlah_terima, dpt.harga_satuan_terima, dpt.sub_total_terima
      from detail_penerimaan dpt
      left join barang b on b.idbarang = dpt.barang_idbarang;

create or replace view v_penjualan as
    select p.idpenjualan, p.created_at, p.status,
           if(p.status = 'S', 'Selesai', 'Tertunda') as status_teks,
           u.username,
           m.persen as margin_persen,
           p.subtotal_nilai, p.ppn, p.total_nilai
      from penjualan p
      left join user u on u.iduser = p.user_iduser
      left join margin_penjualan m
        on m.idmargin_penjualan = p.margin_penjualan_idmargin_penjualan;

create or replace view v_detail_penjualan as
    select dp.penjualan_idpenjualan,
           dp.barang_idbarang, b.nama,
           dp.harga_satuan, dp.jumlah, dp.subtotal
      from detail_penjualan dp
      left join barang b on b.idbarang = dp.barang_idbarang;

create or replace view v_kartu_stok as
    select k.idkartu_stok, k.created_at,
           b.nama,
           case k.jenis_transaksi
               when 'P' then 'Penerimaan'
               when 'J' then 'Penjualan'
               when 'R' then 'Retur'
               else k.jenis_transaksi
           end as jenis_teks,
           k.masuk, k.keluar, k.stock, k.idtransaksi
      from kartu_stok k
      left join barang b on b.idbarang = k.barang_idbarang;

create or replace view v_stok_barang as
    select v.*, fn_stok_barang(v.idbarang) as stok
      from v_barang v;
