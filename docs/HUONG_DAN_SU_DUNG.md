# PostMate — Hướng dẫn sử dụng (dành cho người dùng)

Hướng dẫn thao tác trên giao diện PostMate — công cụ giúp bạn soạn, lên lịch và đăng bài lên fanpage Facebook tự động, quản lý tài khoản/fanpage/cookie/trình duyệt ở một nơi.

> Tài liệu này dành cho **người dùng cuối** thao tác trên màn hình. Bạn không cần biết lập trình.

---

## 1. Đăng nhập

1. Mở trình duyệt và truy cập địa chỉ hệ thống (ví dụ: `http://localhost:8080`).
2. Màn hình **Đăng nhập** hiện ra. Nhập **Tên đăng nhập / Email** và **Mật khẩu**.
3. Bấm **Đăng nhập**.

- Nếu sai thông tin, hệ thống báo lỗi ngay bên dưới ô nhập.
- Sau khi đăng nhập, bạn vào thẳng màn hình **Dashboard**.
- **Đăng xuất:** bấm vào tên/tài khoản ở góc (biểu tượng mũi tên ▾) → chọn **Đăng xuất**.

> Mọi dữ liệu bạn thấy (bài viết, fanpage, cookie…) là **của riêng tài khoản đang đăng nhập**.

---

## 2. Giao diện chung & thanh menu

Bên trái là thanh điều hướng, chia 3 nhóm:

**Chính**
| Mục | Dùng để |
|-----|---------|
| 🟦 Dashboard | Xem tổng quan, số liệu, sức khỏe hệ thống |
| ✏️ Tạo nội dung | Soạn bài mới, lưu nháp / lên lịch / đăng ngay |
| 📅 Lịch đăng | Quản lý các bài đã hẹn giờ |
| 📄 Bài viết | Xem toàn bộ bài & trạng thái |

**Facebook**
| Mục | Dùng để |
|-----|---------|
| 👤 Tài khoản | Quản lý tài khoản Facebook đăng bài |
| 🚩 Fanpage | Quản lý các fanpage |
| 🪟 Trình duyệt | Quản lý hồ sơ trình duyệt chống phát hiện |
| 🍪 Cookie | Quản lý phiên đăng nhập |
| 🕘 Nhật ký | Lịch sử hoạt động *(đang phát triển)* |

**Khác**
| Mục | Dùng để |
|-----|---------|
| 🤖 AI Agent | Hỗ trợ tạo nội dung bằng AI *(đang phát triển)* |
| ⚙️ Cài đặt | Cấu hình hệ thống |

**Thao tác chung trên hầu hết màn danh sách:**
- **Ô tìm kiếm / bộ lọc** ở đầu trang → chọn xong bấm **Lọc / Áp dụng** để cập nhật danh sách.
- **Phân trang** ở cuối danh sách để chuyển trang.
- **Bấm vào một dòng** để mở **bảng chi tiết** trượt ra bên phải.
- Các nút hành động (Xóa, Nhân bản, Đăng nhập lại…) thường yêu cầu **xác nhận** trước khi thực hiện, và hiện **thông báo** (toast) ở góc màn hình sau khi xong.

---

## 3. Dashboard — xem tổng quan

Màn hình đầu tiên sau khi đăng nhập.

- **Thẻ số liệu:** số bài nháp / đã lên lịch / đã đăng / thất bại, cùng mức tăng-giảm so với kỳ trước.
- **Biểu đồ hiệu suất** theo thời gian và **biểu đồ phân bổ** bài theo trạng thái/kênh.
- **Sức khỏe hệ thống:** tình trạng trình duyệt và cookie (còn tốt / sắp hết hạn / lỗi).
- **Chọn khoảng thời gian** rồi bấm **Áp dụng** để đổi phạm vi số liệu.
- **Tự động làm mới:** bật/tắt để dữ liệu tự cập nhật định kỳ.

---

## 4. Tạo nội dung — soạn & đăng bài

Đây là màn hình chính để tạo bài viết. Vào menu **Tạo nội dung**.

### Các bước
1. **Chọn Fanpage** muốn đăng (bắt buộc). Khi chọn fanpage, **Trình duyệt** liên kết sẵn sẽ tự điền.
2. **Chọn loại nội dung** (văn bản, hình ảnh, video…).
3. Nhập **Tiêu đề** (tùy chọn) và **Nội dung** bài viết (bắt buộc). Bộ đếm ký tự hiển thị độ dài.
4. **Thêm ảnh/video:** dán **đường dẫn (URL)** vào ô media, **mỗi dòng một liên kết**.
5. **Hẹn giờ đăng:** chọn **Ngày** và **Giờ**. Có thể chọn **Lặp lại** (nếu muốn đăng định kỳ).
6. Tùy chọn thêm: **Tự rút gọn link**, **Tắt thông báo bình luận**, **Tự động chia sẻ**, và **Ghi chú** nội bộ.

### 3 nút hành động
| Nút | Kết quả |
|-----|---------|
| **Lưu nháp** | Lưu lại để chỉnh sau, chưa đăng. Không bắt buộc nhập đủ. |
| **Lên lịch** | Đưa bài vào hàng chờ, đăng đúng ngày-giờ đã hẹn. |
| **Đăng ngay** | Đưa bài vào hàng chờ với thời gian chạy là hiện tại. Bài sẽ được đăng khi cron/worker xử lý job. |

- Với **Lên lịch** / **Đăng ngay**, hệ thống kiểm tra bạn đã chọn fanpage và nhập nội dung chưa; thiếu sẽ báo lỗi.
- Với **Đăng ngay**, sau khi bấm thành công bài chưa được publish trực tiếp trong request của giao diện. Backend tạo job trong `post_jobs` với giờ chạy là hiện tại; cần cron/worker gọi endpoint xử lý thì bài mới được đẩy lên Facebook.
- Trên production InfinityFree, không chạy `php bin/worker.php --loop`; dùng HTTP cron gọi `/api/cron/posting/run` để xử lý hàng chờ.
- Sau khi lưu thành công, hệ thống chuyển bạn sang màn **Bài viết**.

---

## 5. Lịch đăng — quản lý bài đã hẹn giờ

Vào menu **Lịch đăng** để xem các bài đang chờ tới giờ đăng.

- Lọc theo fanpage, trạng thái, khoảng thời gian → **Áp dụng**.
- Bấm một bài để xem **chi tiết** (nội dung, fanpage, thời gian hẹn).
- **Hủy lịch:** bấm nút hủy trên bài để gỡ khỏi hàng chờ (bài sẽ không đăng nữa).

---

## 6. Bài viết — theo dõi tất cả bài & trạng thái

Vào menu **Bài viết** để xem toàn bộ bài của bạn.

- **Thẻ thống kê** ở trên: tổng số theo từng trạng thái.
- **Bộ lọc:** từ khóa, trạng thái, fanpage, trình duyệt → **Lọc**.
- **Trạng thái bài** hiển thị bằng nhãn màu: Nháp, Đã lên lịch, Đang xử lý, Đã đăng, Thất bại, Hết hạn.
- Bấm một bài để mở **chi tiết** với các tab: **Thông tin**, **Timeline** (tiến trình thực thi), **Tương tác** (hiệu suất).

**Hành động trên từng bài:**
| Nút | Kết quả |
|-----|---------|
| **Nhân bản** | Tạo một bản sao ở dạng **nháp** để chỉnh và đăng lại. |
| **Xóa** | Xóa bài (có hỏi xác nhận). |

---

## 7. Tài khoản Facebook

Vào menu **Tài khoản** để quản lý các tài khoản dùng để đăng bài.

- Xem danh sách, tìm kiếm, lọc theo trạng thái; bấm để xem **chi tiết**.
- **Đăng nhập lại:** khi tài khoản bị đăng xuất hoặc dính checkpoint, bấm để yêu cầu đăng nhập lại.
- **Xóa:** gỡ tài khoản khỏi hệ thống (các bài đã hẹn liên quan sẽ bị hủy).

> ⚠️ **Chưa có chức năng THÊM/KẾT NỐI tài khoản trên giao diện.** Màn hình này chỉ quản lý các tài khoản **đã có sẵn**. Xem [mục 8.bis](#8bis-kết-nối-tài-khoản--fanpage--trang-cá-nhân-chưa-hỗ-trợ) để biết chi tiết.

---

## 8. Fanpage

Vào menu **Fanpage** để quản lý các trang bạn sẽ đăng bài lên.

- Mỗi fanpage hiển thị **trạng thái** và cờ **có thể đăng hay không** (phụ thuộc token/cookie còn hạn, tài khoản không bị chặn, có kênh đăng khả dụng).
- **Đăng nhập lại:** làm mới quyền đăng cho fanpage.
- **Gỡ liên kết:** bỏ fanpage khỏi hệ thống (bài đã hẹn liên quan bị hủy).

> Fanpage nào bật kênh **Graph API** sẽ đăng qua API (ổn định hơn); còn lại dùng **trình duyệt** làm phương án dự phòng.

> ⚠️ **Chưa có chức năng KẾT NỐI/LIÊN KẾT fanpage mới trên giao diện** — xem [mục 8.bis](#8bis-kết-nối-tài-khoản--fanpage--trang-cá-nhân-chưa-hỗ-trợ).

---

## 8.bis. Kết nối tài khoản / fanpage / trang cá nhân *(CHƯA HỖ TRỢ)*

Đây là **giới hạn quan trọng của phiên bản hiện tại**: hệ thống **chưa có bất kỳ đường nào trên giao diện** để đưa một tài khoản Facebook, fanpage hay trang cá nhân **mới** vào hệ thống.

**Cụ thể hiện chưa có:**
- Nút/màn "Thêm tài khoản", "Đăng nhập Facebook", hay quét mã đăng nhập.
- Nút "Kết nối fanpage" / "Liên kết trang cá nhân".
- Luồng nhập cookie hoặc token thủ công từ giao diện.

**Các màn *Tài khoản / Fanpage / Cookie* hiện chỉ:** hiển thị, quản lý và thao tác (đăng nhập lại, làm mới, xóa, gỡ liên kết…) trên những bản ghi **đã tồn tại sẵn** trong cơ sở dữ liệu.

**Tạm thời làm sao để có dữ liệu?**
- Dữ liệu tài khoản/fanpage phải được **nạp trực tiếp vào cơ sở dữ liệu** (bảng `facebook_accounts`, `fanpages`, `cookies` — do quản trị/kỹ thuật viên thực hiện).
- Với fanpage bạn **sở hữu**: có thể khai báo **Meta App** ở màn **Cài đặt → Meta App** (nhập App ID / App Secret / System User Token) rồi cấp Page Token — nhưng bản ghi fanpage vẫn cần tồn tại sẵn trong hệ thống trước.

**Dự kiến bổ sung sau:** luồng "Thêm tài khoản / Kết nối fanpage" (đăng nhập Facebook + lấy cookie/token tự động qua trình duyệt anti-detect hoặc Graph API) sẽ được xây dựng trong bản cập nhật tiếp theo, đi kèm với bộ phận worker tự động đăng bài.

---

## 9. Trình duyệt (hồ sơ chống phát hiện)

Vào menu **Trình duyệt**. Đây là các hồ sơ Chrome “chống phát hiện” dùng khi đăng bằng trình duyệt.

**Hành động trên từng hồ sơ:**
| Nút | Kết quả |
|-----|---------|
| **Khởi động** | Bật hồ sơ trình duyệt. |
| **Dừng** | Tắt hồ sơ. |
| **Khởi động lại** | Tắt rồi bật lại. |
| **Mở** | Mở cửa sổ trình duyệt để thao tác tay (đăng nhập, xử lý xác minh…). |
| **Xóa** | Xóa hồ sơ (có xác nhận). |

- Chi tiết hồ sơ cho biết đang chạy hay dừng, thuộc máy chủ/proxy nào.

---

## 10. Cookie (phiên đăng nhập)

Vào menu **Cookie** để quản lý phiên đăng nhập của các tài khoản.

- **Thẻ thống kê:** số cookie còn tốt / sắp hết hạn / lỗi.
- **Đăng nhập:** tạo phiên mới cho tài khoản.
- **Làm mới:** gia hạn một cookie.
- **Làm mới tất cả:** gia hạn hàng loạt các cookie sắp hết hạn (một nút ở đầu trang).
- **Xóa:** xóa cookie không dùng.

> Vì lý do bảo mật, **nội dung cookie không bao giờ hiển thị** trên màn hình.

---

## 11. Cài đặt

Vào menu **Cài đặt** để cấu hình hệ thống.

- **Ngôn ngữ & Múi giờ:** chọn rồi bấm **Lưu**.
- **Cấu hình mặc định:** ví dụ kênh đăng ưu tiên (API/trình duyệt), cho phép dùng trình duyệt dự phòng… → **Lưu**.
- **Bật/tắt nhanh** các tùy chọn bằng công tắc (toggle).
- **Sao lưu ngay:** tạo bản sao lưu dữ liệu.
- Xem **thông tin hệ thống** và dung lượng lưu trữ đang dùng.

---

## 12. Luồng chạy của hệ thống

### 12.1. Cách hệ thống vận hành (tổng thể)

```
  Bạn (trình duyệt)                Máy chủ (WAMP :8080)
 ┌─────────────────┐   gọi API   ┌──────────────────────────────┐
 │  Giao diện web  │ ─────────▶  │  Backend PHP  ──▶  CSDL MySQL │
 │  (Angular SPA)  │ ◀───────── │  (xử lý & lưu dữ liệu)        │
 └─────────────────┘   dữ liệu   └──────────────────────────────┘
```

- Mọi thao tác bạn bấm trên màn hình được gửi về máy chủ, xử lý rồi trả kết quả ngay (kèm thông báo thành công/lỗi ở góc màn hình).
- Giao diện và máy chủ chạy **cùng một địa chỉ** nên bạn chỉ cần mở đúng đường dẫn hệ thống là dùng được.

### 12.2. Luồng một bài viết (từ lúc soạn tới khi đăng)

```
 Soạn bài ──▶ [Nháp] ──▶ [Đã lên lịch] ──▶ [Đang xử lý] ──▶ [Đã đăng]
   │             │              │                │              │
 Lưu nháp   Đăng ngay      chờ tới giờ      hệ thống đăng   xong, có link
                                                │
                                     lỗi ──▶ [Thất bại] / [Hết hạn]
```

Diễn giải từng bước:

1. **Soạn & lưu** — Bạn tạo bài ở màn *Tạo nội dung* rồi chọn một trong ba:
   - **Lưu nháp** → bài ở trạng thái *Nháp*, chưa đăng.
   - **Lên lịch** → bài chuyển sang *Đã lên lịch*, chờ đúng ngày-giờ đã hẹn.
   - **Đăng ngay** → bài chuyển sang *Đã lên lịch* với thời gian chạy là hiện tại, chờ cron/worker xử lý ngay lượt kế tiếp.
2. **Kiểm tra trước khi xếp lịch** — Hệ thống kiểm tra fanpage có **đủ điều kiện đăng** không (token/cookie còn hạn, tài khoản không bị chặn) và tự chọn **kênh đăng**:
   - Fanpage bật **Graph API** → đăng qua API (ưu tiên, ổn định).
   - Ngược lại → đăng bằng **trình duyệt** chống phát hiện (phương án dự phòng).
   - *Nếu đăng cho nhiều fanpage cùng lúc: fanpage nào không đủ điều kiện sẽ bị bỏ qua, các fanpage còn lại vẫn được xếp.*
3. **Tới giờ, hệ thống đăng bài** — Khi cron/worker chạy, bài chuyển sang *Đang xử lý*, thực hiện đăng qua kênh đã chọn, rồi **xác nhận bài thật sự lên trang**.
4. **Kết quả:**
   - Thành công → *Đã đăng*, lưu lại liên kết bài và bắt đầu thu thập số liệu tương tác.
   - Thất bại → tùy loại lỗi: bị giới hạn tần suất sẽ **tự thử lại sau**; lỗi nghiêm trọng (vi phạm nội dung, mất quyền) chuyển *Thất bại*; nếu tài khoản dính checkpoint, các bài đang chờ của tài khoản đó sẽ bị hủy và có cảnh báo ở màn *Tài khoản*.
   - Quá hạn mà chưa kịp chạy → *Hết hạn*.
5. **Đăng định kỳ (lặp lại)** — Nếu bạn đặt *Lặp lại* khi soạn, hệ thống tự sinh lượt đăng cho lần kế tiếp.

> **Theo dõi ở đâu:** trạng thái từng bài xem ở màn **Bài viết** (mở chi tiết có tab *Timeline* để xem tiến trình); các bài đang chờ xem ở **Lịch đăng**; số liệu tổng hợp xem ở **Dashboard**.

### 12.3. Cách chạy cron để đăng bài

Backend hiện tại xử lý đăng bài qua hàng chờ:

- **Đăng ngay**: tạo job có giờ chạy là hiện tại.
- **Lên lịch**: tạo job có giờ chạy đúng theo lịch đã chọn, hoặc giao lịch trực tiếp cho Facebook nếu đủ điều kiện Graph API native schedule.
- **Cron/worker**: là phần thực sự lấy job tới hạn và gọi Graph API/trình duyệt để đăng.

Endpoint cần gọi:

```text
GET|POST https://postmate.infinityfree.io/api/cron/posting/run?i=1
```

Cần gửi secret bằng một trong hai cách:

```text
Header: X-Cron-Secret: <postingSecret>
```

hoặc:

```text
Query/body: secret=<postingSecret>
```

Cách chạy thực tế:

1. Cấu hình external cron gọi endpoint này mỗi 1 phút để tự xử lý bài tới hạn.
2. Sau khi bấm **Đăng ngay**, nếu muốn xử lý liền, có thể gọi endpoint cron ngay một lượt.
3. Mỗi lần gọi cron sẽ xử lý một số job tới hạn, mặc định tối đa 5 job/lượt.
4. Nếu response có `processed > 0`, nghĩa là đã có job được xử lý.
5. Nếu response là `processed = 0`, `pending = 0`, `errors = []`, nghĩa là hiện không có job tới hạn hoặc job đã được xử lý trước đó.
6. Nếu bài lỗi, xem màn **Bài viết** hoặc chi tiết bài để đọc `lastError`/Timeline.

Ví dụ response thành công:

```json
{
  "code": 1,
  "data": {
    "processed": 1,
    "expired": 0,
    "pending": 0,
    "errors": []
  }
}
```

Tóm lại: **click Đăng ngay xong vẫn cần cron/worker chạy**. Nếu không có cron/worker, bài sẽ nằm ở trạng thái *Đã lên lịch* hoặc *Đang chờ* và chưa tự lên Facebook.

### 12.4. ⚠️ Tình trạng hiện tại (quan trọng)

- Các bước **soạn bài, lưu nháp, lên lịch, quản lý tài khoản/fanpage/cookie/trình duyệt/cài đặt** đã hoạt động thật và lưu vào cơ sở dữ liệu.
- **Chưa có chức năng thêm/kết nối tài khoản, fanpage hay trang cá nhân mới trên giao diện** — dữ liệu phải nạp sẵn vào CSDL (xem [mục 8.bis](#8bis-kết-nối-tài-khoản--fanpage--trang-cá-nhân-chưa-hỗ-trợ)).
- **Bộ phận tự động đăng bài chạy bằng HTTP cron**: production cần external cron gọi `/api/cron/posting/run`. Nếu cron không chạy, bài ở trạng thái *Đã lên lịch* sẽ chưa tự động đẩy lên Facebook khi tới giờ.
- Một số nút như **Đăng nhập lại, Làm mới cookie, Khởi động/Mở trình duyệt** hiện trả kết quả **mô phỏng** (báo thành công) nhưng chưa thực sự điều khiển trình duyệt/Facebook.
- Đây là thông tin để bạn không hiểu nhầm là lỗi; các phần này sẽ được hoàn thiện ở bản cập nhật sau.

---

## 13. Quy trình khuyến nghị cho người mới

1. **Đăng nhập** vào hệ thống.
2. Vào **Cài đặt** kiểm tra ngôn ngữ, múi giờ, kênh đăng ưu tiên.
3. Đảm bảo đã có sẵn **tài khoản / fanpage** trong hệ thống. *(Hiện phải nạp sẵn vào CSDL — chưa thêm được từ giao diện, xem [mục 8.bis](#8bis-kết-nối-tài-khoản--fanpage--trang-cá-nhân-chưa-hỗ-trợ).)*
4. Vào **Cookie** đảm bảo tài khoản có phiên đăng nhập hợp lệ; (nếu đăng bằng trình duyệt) chuẩn bị **Trình duyệt**.
5. Kiểm tra **Fanpage** đang ở trạng thái **có thể đăng**.
6. Vào **Tạo nội dung** để soạn bài → **Lên lịch** hoặc **Đăng ngay**.
7. Đảm bảo external cron đang gọi `/api/cron/posting/run`, nhất là khi muốn bài **Đăng ngay** được xử lý liền.
8. Theo dõi tiến trình ở **Lịch đăng**, **Bài viết** và **Dashboard**.

---

## 14. Câu hỏi thường gặp

**Không đăng nhập được?** Kiểm tra lại tên đăng nhập/mật khẩu; nếu vẫn lỗi, liên hệ quản trị hệ thống.

**Bài đã lên lịch hoặc bấm Đăng ngay nhưng chưa thấy đăng?** Kiểm tra external cron đã gọi `/api/cron/posting/run` chưa, fanpage còn ở trạng thái **có thể đăng**, cookie/token chưa hết hạn, và trình duyệt (nếu dùng) đang chạy. Nếu bài thất bại, mở chi tiết bài để xem `lastError`/Timeline.

**Nút “Đăng nhập lại” / “Làm mới” để làm gì?** Khi tài khoản bị đăng xuất hoặc phiên hết hạn, dùng các nút này để khôi phục quyền đăng bài.

**Làm sao thêm tài khoản Facebook / kết nối fanpage mới?** Phiên bản hiện tại **chưa hỗ trợ** thêm từ giao diện — dữ liệu phải được nạp sẵn vào cơ sở dữ liệu bởi quản trị/kỹ thuật viên. Chi tiết xem [mục 8.bis](#8bis-kết-nối-tài-khoản--fanpage--trang-cá-nhân-chưa-hỗ-trợ).

**Một số mục ghi “đang phát triển” (Nhật ký, AI Agent)?** Các tính năng này sẽ được bổ sung trong bản cập nhật sau.

---

*Tài liệu kỹ thuật chi tiết (nghiệp vụ, cơ sở dữ liệu, API) dành cho lập trình viên xem trong thư mục [`docs/Features/`](./Features/README.md) và [`PHAN_TICH_HE_THONG.md`](./PHAN_TICH_HE_THONG.md).*
