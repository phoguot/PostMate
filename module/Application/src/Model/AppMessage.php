<?php
/**
 * Translate Message
 */

namespace Application\Model;

class AppMessage
{
    // Common message
    public const COMMON_BACK_TO_HOME = 'Về trang chủ';
    public const COMMON_404 = 'Không tìm thấy nội dung yêu cầu';
    public const COMMON_403 = 'Bạn không có quyền truy cập trang này';
    public const SYSTEM_ERROR = 'Hệ thống đã xảy ra lỗi';
    public const COMMON_401 = 'Bạn chưa đăng nhập';

    public const ADD_SUCCESSFULLY = 'Thêm dữ liệu thành công';
    public const SAVE_SUCCESSFULLY = 'Lưu dữ liệu thành công';
    public const DELETE_SUCCESSFULLY = 'Xóa dữ liệu thành công';
    public const UPDATE_SUCCESSFULLY = 'Cập nhật dữ liệu thành công';

    public const MSG_RECORD_NO_PERMISSION = 'Bạn không có quyền sửa bản ghi này';
    public const CANNOT_DELETE = 'Bạn không có quyền xóa';

    // Data
    public const EXIST_DATA = 'Dữ liệu đã tồn tại';
    public const EXISTED_DATA = 'Dữ liệu đã tồn tại';
    public const INVALID_DATA = 'Dữ liệu không hợp lệ';
    public const NO_DATA = 'Không tìm thấy dữ liệu';
    public const DATA_HAS_CREATED = 'Dữ liệu đã được khởi tạo';
    public const DATA_HAS_UPDATED = 'Dữ liệu đã được cập nhật';
    public const API_NOT_EXISTED = 'API không tồn tại';
    public const DELETE_DATA_SUCCESS = 'Xóa dữ liệu thành công';
    public const MSG_ERROR_WHEN_DELETE = 'Có lỗi trong quá trình xóa';
    public const NUMBER_OF_RECORD_EXCEEDED_ALLOW_LIMIT = 'Số bản ghi vượt quá giới hạn cho phép';

    // Account / user auth
    public const USER_LOCKED = 'Tài khoản của bạn đã bị khóa';
    public const USER_INACTIVE = 'Tài khoản của bạn chưa được kích hoạt';
    public const ACCOUNT_NOT_FOUND = 'Tài khoản không tồn tại';
    public const ACCOUNT_NOT_EMPTY = 'Tên tài khoản không được để trống';
    public const USER_HAVE_NOT_IDENTITY = 'Bạn không có danh tính cho trang này';
    public const USER_NOT_FOUND = 'Không tìm thấy người dùng';

    // Validators
    public const VALIDATOR_PASSWORD_INVALID = 'Mật khẩu không hợp lệ';
    public const VALIDATOR_PASSWORD_TOO_SHORT = 'Mật khẩu phải có từ 8 ký tự trở lên';
    public const VALIDATOR_PASSWORD_TOO_LONG = 'Mật khẩu không hợp lệ (quá dài)';
    public const VALIDATOR_EMAIL_INVALID = 'Email không hợp lệ';
    public const VALIDATOR_EMAIL_EXISTED = 'Địa chỉ email đã tồn tại';
    public const VALIDATOR_EMAIL_EMPTY = 'Bạn chưa nhập Email';
    public const VALIDATOR_MOBILE_EMPTY = 'Bạn chưa nhập số điện thoại';
    public const VALIDATOR_MOBILE_EXISTED = 'Số điện thoại đã tồn tại';
    public const VALIDATOR_USERNAME_EXISTED = 'Tên đăng nhập đã tồn tại';
    public const VALIDATOR_REQUIRED = 'Trường dữ liệu không được để trống';
    public const EMAIL_INVALID = 'Email không hợp lệ';
    public const MOBILE_NUMBER_INVALID = 'Số điện thoại không hợp lệ';
    public const MOBILE_NUMBER_INVALID_TOO_SHORT = 'Số điện thoại không hợp lệ (quá ngắn)';
    public const MOBILE_NUMBER_INVALID_TOO_LONG = 'Số điện thoại không hợp lệ (quá dài)';

    // Status
    public const STATUS_NOT_EMPTY = 'Trạng thái không được để trống';
    public const STATUS_INVALID = 'Trạng thái không hợp lệ';

    // File
    public const NO_FILE_SELECT = 'Bạn chưa chọn file';
    public const FILE_REQUIRED = 'Trường dữ liệu file không được để trống';
    public const FILE_UPLOAD_LARGE_THAN_3MB = 'File upload không được lớn hơn 3Mb';
    public const FILE_UPLOAD_INVALID_FORMAT = 'Định dạng file không hợp lệ';
    public const FILE_UPLOAD_INVALID = 'File không hợp lệ';
    public const FILE_UPLOAD_INVALID_TYPE = 'Loại file không hợp lệ';
    public const FILE_FORMAT_NOT_SUPPORTED = 'Không hỗ trợ định dạng file';
    public const FILE_UPLOAD_FAILED = 'Upload không thành công';

    // Date / time
    public const DATE_FORMAT_INVALID = 'Định dạng ngày không hợp lệ';
    public const HOUR_SELECT_INVALID = 'Giờ lựa chọn không hợp lệ';
    public const TIME_CHECKIN_CANNOT_GREATER_THAN_TIME_CHECKOUT = 'Thời gian đến không được lớn hơn thời gian về';
    public const FROM_DATE_CANNOT_GREATER_THAN_TO_DATE = 'Thời gian từ không được lớn hơn thời gian đến';
    public const START_DATE_CANNOT_GREATER_END_TO_DATE = 'Thời gian bắt đầu không được lớn hơn thời gian kết thúc';

    // Category
    public const CATEGORY_NAME_IS_EXISTED = 'Tên danh mục đã tồn tại';
    public const CATEGORY_NOT_FOUND = 'Danh mục không tồn tại';
    public const PARENT_CATEGORY_INVALID = 'Danh mục cha không hợp lệ';
    public const PARENT_CATEGORY_IS_CHILD = 'Danh mục cha không hợp lệ';

    // User Module
    public const USER_PASSWORD_IS_EMPTY = 'Bạn chưa điền mật khẩu';
    public const USER_USERNAME_IS_EMPTY = 'Bạn chưa điền tên đăng nhập';
    public const USER_MOBILE_IS_EMPTY = 'Bạn chưa điền số điện thoại';
    public const USER_USERNAME_PASSWORD_IS_EMPTY = 'Tên đăng nhập và mật khẩu không được để trống';
    public const USER_USERNAME_PASSWORD_IS_SAME = 'Tên đăng nhập và mật khẩu không được giống nhau';
    public const USER_PASSWORD_AT_LEASE_ONE_UPPERCASE = 'Mật khẩu phải có ít nhất 1 ký tự viết hoa';
    public const USER_PASSWORD_AT_LEASE_ONE_LOWERCASE = 'Mật khẩu phải có ít nhất 1 ký tự viết thường';
    public const USER_PASSWORD_AT_LEASE_ONE_NUMBER = 'Mật khẩu phải có ít nhất 1 chữ số';
    public const USER_PASSWORD_EASY_GUESS = 'Mật khẩu dễ nhận biết';
}
