<?php

namespace Database\Seeders;

use App\Models\ChiHoi;
use App\Models\PhanQuyen;
use App\Models\TaiKhoan;
use App\Models\ThanhVien;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Tạo tài khoản mẫu cho 19 chi hội chuẩn (config/chi_hoi.php):
 *   - 1 Admin Chi hội  : admin-chNN@scaros.test   (mã HV2NN) — duyệt phim/bảo lãnh cấp Chi hội
 *   - 1 Hội viên       : member-chNN@scaros.test  (mã HV1NN) — nộp phim, bỏ phiếu
 * NN = số thứ tự chi hội theo config/chi_hoi.php (01..19). Mật khẩu chung: password
 * Chạy: php artisan db:seed --class=ChiHoiAccountSeeder   (chạy lại nhiều lần vẫn an toàn)
 */
class ChiHoiAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Không chạy tài khoản mẫu trong môi trường production.');
        }

        // Đảm bảo 19 chi hội đã tồn tại
        $this->call(ChiHoiSeeder::class);

        foreach (config('chi_hoi') as $index => $cauHinh) {
            $so = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $chiHoi = ChiHoi::where('ten_chi_hoi', $cauHinh['ten_chi_hoi'])->firstOrFail();

            // 1) Admin Chi hội
            $admin = $this->taoThanhVien(
                email: "admin-ch{$so}@scaros.test",
                vaiTroTaiKhoan: 'admin',
                maHoiVien: "HV2{$so}",
                chiHoi: $chiHoi,
                hoTen: "Quản trị mẫu — {$chiHoi->ten_chi_hoi}",
                namGiaNhap: 2015,
            );
            PhanQuyen::updateOrCreate(
                ['ma_thanh_vien' => $admin->getKey()],
                ['vai_tro' => 'ban_dieu_hanh_chi_hoi', 'ma_chi_hoi' => $chiHoi->getKey()]
            );

            // 2) Hội viên thường
            $this->taoThanhVien(
                email: "member-ch{$so}@scaros.test",
                vaiTroTaiKhoan: 'member',
                maHoiVien: "HV1{$so}",
                chiHoi: $chiHoi,
                hoTen: "Hội viên mẫu — {$chiHoi->ten_chi_hoi}",
                namGiaNhap: 2020,
            );
        }
    }

    private function taoThanhVien(string $email, string $vaiTroTaiKhoan, string $maHoiVien, ChiHoi $chiHoi, string $hoTen, int $namGiaNhap): ThanhVien
    {
        $taiKhoan = TaiKhoan::updateOrCreate(
            ['email' => $email],
            [
                'mat_khau_hash' => Hash::make('password'),
                'vai_tro' => $vaiTroTaiKhoan,
                'nguon_tao' => 'seeder',
            ]
        );

        return ThanhVien::updateOrCreate(
            ['ma_tai_khoan' => $taiKhoan->getKey()],
            [
                'ma_hoi_vien' => $maHoiVien,
                'ma_chi_hoi' => $chiHoi->getKey(),
                'ho_ten' => $hoTen,
                'trang_thai' => 'hoat_dong',
                'nam_gia_nhap' => $namGiaNhap,
                'tieu_su' => 'Tài khoản minh hoạ Scaros; không phải hội viên AMPAS.',
            ]
        );
    }
}
