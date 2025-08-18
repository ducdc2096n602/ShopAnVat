<?php
require_once('helpers/startSession.php');
startRoleSession('customer');
require('layout/header.php');
?>
<style>
    main {
        font-family: "Encode Sans SC", sans-serif;
    }

    .row {
        margin-bottom: 40px;
        line-height: 1.6;
    }

    h3 {
        margin-bottom: 10px;
        color: #d35400;
        font-weight: bold;
    }

    p {
        color: #34495e;
    }

    iframe {
        border: none;
        border-radius: 10px;
        width: 100%;
        max-width: 100%;
        height: 315px;
    }

    @media (max-width: 768px) {
        iframe {
            height: 250px !important;
        }
    }
</style>

<main>
    <div class="container">
        <section class="main">

            <div class="row">
                <h3>Shop ăn vặt là gì?</h3>
                <p>Chúng tôi hiểu rằng những bữa ăn vặt có thể mang lại cho bạn sự thư giãn và tinh thần thoải mái. Thế Giới Ăn Vặt ra đời để giúp bạn đặt món ăn yêu thích một cách nhanh chóng. Đội ngũ giao hàng của chúng tôi luôn sẵn sàng phục vụ bạn tận nơi, đảm bảo món ăn luôn nóng hổi và ngon lành.</p>
            </div>

            <div class="row">
                <h3>Thời gian hoạt động của chúng tôi</h3>
                <p>Chúng tôi phục vụ từ 8h đến 22h mỗi ngày để đảm bảo bạn luôn có những món ăn ngon bất cứ lúc nào trong ngày.</p>
            </div>

            <div class="row">
                <h3>Khi khách hàng cần hỗ trợ?</h3>
                <p>Hệ thống chatbot thông minh sẽ hỗ trợ tư vấn, giải quyết các thắc mắc của bạn một cách nhanh chóng.</p>
            </div>
                   

            <div class="row">
                <h3>Hình thức thanh toán hỗ trợ</h3>
                <p>Bạn có thể thanh toán bằng tiền mặt khi nhận hàng, chuyển khoản ngân hàng, thanh toán qua ví momo, thanh toán bằng Paypal.</p>
            </div>

            <div class="row">
                <h3>Chi phí tính như thế nào?</h3>
                <p>Giá hiển thị trên website bao gồm giá sản phẩm và phí vận chuyển và có khấu trừ khuyến mãi khi khách hàng sử dụng mã voucher giảm giá.</p>
            </div>

            <div class="row">
                <h3>Tôi có thể đặt những món ăn gì?</h3>
                <p>Thực đơn đa dạng của chúng tôi bao gồm:  bánh tráng, cơm cháy, khô gà, bánh, kẹo, mứt và nhiều món ăn vặt hấp dẫn khác chờ bạn khám phá.</p>
            </div>

            

            <!-- BẢN ĐỒ + VIDEO -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15716.184446121564!2d105.74748868715821!3d10.013050000000014!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31a0891c51cc7f7d%3A0xd04a9fcf6685b0cf!2zxIJuIHbhurd0IEPhuqduIFRoxqE!5e0!3m2!1svi!2s!4v1726041884971!5m2!1svi!2s"
                        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                <div class="col-md-6 mb-3">
                    <iframe src="https://www.youtube.com/embed/gj5Hp9tycww?si=eRtEgRU8o8c-fPH5"
                        title="YouTube video player"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen referrerpolicy="strict-origin-when-cross-origin">
                    </iframe>
                </div>
            </div>
        </section>
    </div>
</main>

<?php require('layout/footer.php'); ?>
<?php include('chatbot.php'); ?>
