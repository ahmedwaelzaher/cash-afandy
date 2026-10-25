<?php

namespace Database\Seeders;

use App\Models\StaticPage;
use Illuminate\Database\Seeder;

class StaticPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            'privacy-policy' => [
                'title' => ['en' => 'Privacy Policy', 'ar' => 'سياسة الخصوصية'],
                'content' => [
                    'ar' => <<<'HTML'
                        <p>في كاش أفندي، نحرص على توضيح كيفية التعامل مع بياناتك عند تصفح الكوبونات وعروض الخصم واستخدام حسابك. توضح هذه السياسة البيانات المرتبطة بخدمات الموقع وكيفية التواصل معنا بشأنها.</p>
                        <h2>البيانات التي نتعامل معها</h2>
                        <ul>
                            <li>بيانات الحساب التي تقدمها، مثل الاسم والبريد الإلكتروني وكلمة المرور وبيانات الملف الشخصي، بما فيها النوع ورقم الهاتف وتاريخ الميلاد عند تقديمها.</li>
                            <li>عند استخدام تسجيل الدخول عبر Google أو Facebook، نستقبل بيانات التعريف التي يتيحها مزود الخدمة، مثل الاسم والبريد الإلكتروني ومعرّف الحساب.</li>
                            <li>البريد الإلكتروني الذي تدخله عند الاشتراك في النشرة البريدية، والبيانات التي ترسلها عند التواصل مع الدعم.</li>
                            <li>بيانات تقنية مرتبطة بزيارة الموقع، مثل عنوان الإنترنت وبيانات المتصفح والجلسة والتفضيلات، للمساعدة في تشغيل الموقع وتحديد العروض المناسبة للبلد.</li>
                        </ul>
                        <h2>كيف تُستخدم البيانات؟</h2>
                        <p>تُستخدم البيانات لإنشاء حسابك وإدارته، وتسجيل الدخول والتحقق من البريد واستعادة الوصول، وحفظ تفضيلاتك، وعرض المحتوى المناسب، والرد على استفساراتك، وحماية الموقع من إساءة الاستخدام. ويُستخدم بريد الاشتراك لإرسال أخبار الموقع والعروض.</p>
                        <h2>ملفات الارتباط وأدوات القياس</h2>
                        <p>يستخدم الموقع ملفات الارتباط والتخزين المحلي لتشغيل الجلسات وحفظ بعض التفضيلات مثل البلد والمظهر. وقد يستخدم Google Analytics وMeta Pixel عند تفعيلهما لقياس الزيارات والتفاعل مع المحتوى. يمكنك إدارة ملفات الارتباط من إعدادات المتصفح؛ وقد يؤثر تعطيل بعضها في تسجيل الدخول أو حفظ التفضيلات.</p>
                        <h2>مزودو الخدمة والروابط الخارجية</h2>
                        <p>قد تُعالج البيانات اللازمة للتشغيل بواسطة مزودي الاستضافة والبريد وتسجيل الدخول وأدوات القياس عند استخدامها. وقد تنقلك روابط العروض إلى متاجر أو شبكات تسويق بالعمولة تستخدم أدواتها الخاصة لتتبع الإحالات. تسري سياسات تلك الجهات على البيانات التي تقدمها لها، لذلك راجعها قبل إتمام الشراء.</p>
                        <h2>الاحتفاظ بالبيانات وحمايتها</h2>
                        <p>نحتفظ بالبيانات بالقدر اللازم لتشغيل الحساب والخدمات، ومعالجة طلبات الدعم، ومتطلبات الأمان والالتزامات القانونية المنطبقة. تختلف مدة الاحتفاظ حسب نوع البيانات والغرض منها. لا ترسل كلمات المرور أو بيانات بطاقات الدفع عبر رسائل الدعم.</p>
                        <h2>إدارة بياناتك والتواصل معنا</h2>
                        <p>يمكنك تعديل بياناتك وتفضيلاتك من حسابك. لطلبات الوصول إلى بياناتك أو تصحيحها أو حذفها أو إيقاف رسائل النشرة البريدية، راسل <a href="mailto:support@cashafandy.com"><bdi>support@cashafandy.com</bdi></a>. قد نحتاج إلى التحقق من هويتك قبل تنفيذ الطلب، وتُراجع الطلبات وفق طبيعتها والالتزامات المنطبقة.</p>
                        <h2>تحديثات السياسة</h2>
                        <p>قد نحدّث هذه السياسة لتوضيح تغييرات الخدمات أو طريقة التعامل مع البيانات. يظهر تاريخ آخر تحديث أعلى الصفحة.</p>
                        HTML,
                    'en' => <<<'HTML'
                        <p>Cash Afandy aims to explain how your data is handled when you browse coupons and discount offers or use your account. This policy describes the data associated with our website services and how to contact us about it.</p>
                        <h2>Data we handle</h2>
                        <ul>
                            <li>Account information you provide, such as your name, email address, password and profile details, including gender, phone number and date of birth when supplied.</li>
                            <li>When you sign in through Google or Facebook, identification details made available by that provider, such as your name, email address and account identifier.</li>
                            <li>The email address you enter when subscribing to the newsletter and information you send to support.</li>
                            <li>Technical visit information, such as IP address, browser, session data and preferences, to help operate the website and show offers relevant to your country.</li>
                        </ul>
                        <h2>How data is used</h2>
                        <p>Data is used to create and manage your account, sign you in, verify your email, restore access, save preferences, display relevant content, respond to enquiries and protect the website from misuse. Newsletter subscription addresses are used to send website news and offers.</p>
                        <h2>Cookies and measurement tools</h2>
                        <p>The website uses cookies and local storage to operate sessions and remember preferences such as country and appearance. Google Analytics and Meta Pixel may be used when enabled to measure visits and content interactions. You can manage cookies through your browser settings; disabling some cookies may affect sign-in or saved preferences.</p>
                        <h2>Service providers and external links</h2>
                        <p>Data needed to operate the service may be processed by hosting, email, sign-in and measurement providers when used. Offer links may take you to stores or affiliate networks that use their own referral tracking tools. Their policies apply to information you provide to them, so review those policies before purchasing.</p>
                        <h2>Retention and protection</h2>
                        <p>Data is retained as needed to operate accounts and services, handle support requests, meet security needs and comply with applicable legal obligations. Retention varies with the type and purpose of the data. Do not send passwords or payment card details in support messages.</p>
                        <h2>Managing your data and contacting us</h2>
                        <p>You can edit your details and preferences in your account. To request access, correction or deletion of your data, or to stop newsletter emails, contact <a href="mailto:support@cashafandy.com"><bdi>support@cashafandy.com</bdi></a>. We may need to verify your identity before acting, and requests are reviewed according to their nature and applicable obligations.</p>
                        <h2>Policy updates</h2>
                        <p>We may update this policy to explain changes to our services or data handling. The latest update date appears at the top of this page.</p>
                        HTML,
                ],
            ],
            'terms-and-conditions' => [
                'title' => ['en' => 'Terms and Conditions', 'ar' => 'الشروط والأحكام'],
                'content' => [
                    'ar' => <<<'HTML'
                        <p>مرحبًا بك في كاش أفندي. توضح هذه الشروط طريقة استخدام الموقع والكوبونات وعروض الخصم المعروضة عليه. يرجى قراءتها قبل استخدام الخدمات أو الاستفادة من أي عرض.</p>
                        <h2>طبيعة الخدمة</h2>
                        <p>يساعدك كاش أفندي على اكتشاف كوبونات الخصم وعروض التوفير لدى المتاجر والجهات الشريكة. عند الانتقال إلى متجر خارجي، تتم عملية الشراء والدفع لدى الجهة التي تقدم المنتج أو الخدمة، وتخضع لشروطها.</p>
                        <h2>الحساب والاستخدام المسؤول</h2>
                        <p>قد تتطلب بعض الخدمات إنشاء حساب. قدّم بيانات صحيحة، واحفظ بيانات دخولك، وتواصل معنا إذا اشتبهت في استخدام حسابك دون إذنك. يُحظر استخدام الموقع للتحايل على العروض أو نشر محتوى ضار أو محاولة تعطيل الخدمة أو الوصول إلى بيانات الغير.</p>
                        <h2>الكوبونات والعروض</h2>
                        <p>لكل عرض شروطه الخاصة، مثل البلد والمنتجات المشمولة والحد الأدنى للطلب وتاريخ الانتهاء وإمكانية الجمع مع خصومات أخرى. راجع التفاصيل وتأكد من تطبيق الخصم في المتجر قبل الدفع. قد يعدّل المتجر العرض أو يوقفه، وقد تنتهي بعض الأكواد قبل تحديثها على الموقع.</p>
                        <h2>روابط التسويق بالعمولة</h2>
                        <p>قد يحصل كاش أفندي على عمولة من بعض الروابط عند إتمام عملية مؤهلة لدى جهة شريكة. لا تعني هذه العمولة ضمان قبول الكوبون لكل طلب.</p>
                        <h2>الشراء لدى المتاجر</h2>
                        <p>المتجر أو مقدم الخدمة مسؤول عن المنتجات والأسعار والدفع والشحن والإلغاء والاسترجاع والضمان وفق شروطه والحقوق القانونية المنطبقة. استفسارات الطلبات تُوجّه أولًا إلى المتجر، ويمكنك إبلاغنا بمشكلة في بيانات العرض لمراجعتها.</p>
                        <h2>المحتوى وتوافر الخدمة</h2>
                        <p>نحرص على عرض معلومات واضحة، لكن قد تحدث أخطاء أو انقطاعات أو تغييرات في العروض. لا نضمن توافر كل عرض أو استمرار الخدمة دون انقطاع. لا تنتقص هذه الشروط من الحقوق التي لا يجوز استبعادها بموجب القانون المنطبق.</p>
                        <h2>التحديثات والتواصل</h2>
                        <p>قد تُحدّث هذه الشروط مع تطور الخدمات، ويظهر تاريخ آخر تحديث أعلى الصفحة. للاستفسارات، راسل <a href="mailto:support@cashafandy.com"><bdi>support@cashafandy.com</bdi></a>، وللشراكات راسل <a href="mailto:partners@cashafandy.com"><bdi>partners@cashafandy.com</bdi></a>.</p>
                        HTML,
                    'en' => <<<'HTML'
                        <p>Welcome to Cash Afandy. These terms explain how to use the website and the coupons and discount offers it displays. Please read them before using the services or redeeming an offer.</p>
                        <h2>Our service</h2>
                        <p>Cash Afandy helps you discover discount coupons and savings offers from stores and partners. When you visit an external store, purchases and payments take place with the product or service provider and are subject to its terms.</p>
                        <h2>Accounts and responsible use</h2>
                        <p>Some services may require an account. Provide accurate details, keep your sign-in credentials safe and contact us if you suspect unauthorised use. Do not use the website to abuse offers, publish harmful content, disrupt the service or access other people's data.</p>
                        <h2>Coupons and offers</h2>
                        <p>Each offer has its own conditions, such as country, eligible products, minimum spend, expiry and whether it can be combined with other discounts. Read the details and confirm that the store applies the discount before paying. Stores may change or withdraw offers, and some codes may expire before the website is updated.</p>
                        <h2>Affiliate links</h2>
                        <p>Cash Afandy may earn a commission through some links when a qualifying transaction is completed with a partner. This does not guarantee that a coupon will be accepted for every order.</p>
                        <h2>Purchases from stores</h2>
                        <p>The store or service provider is responsible for products, pricing, payments, delivery, cancellations, returns and warranties under its terms and applicable legal rights. Direct order enquiries to the store first. You can also report inaccurate offer details to us for review.</p>
                        <h2>Content and availability</h2>
                        <p>We aim to provide clear information, but errors, interruptions and offer changes may occur. We do not guarantee that every offer will remain available or that the service will be uninterrupted. These terms do not limit rights that cannot be excluded under applicable law.</p>
                        <h2>Updates and contact</h2>
                        <p>These terms may change as our services develop. The latest update date appears at the top of this page. For enquiries, email <a href="mailto:support@cashafandy.com"><bdi>support@cashafandy.com</bdi></a>. For partnerships, email <a href="mailto:partners@cashafandy.com"><bdi>partners@cashafandy.com</bdi></a>.</p>
                        HTML,
                ],
            ],
            'contact-us' => [
                'title' => ['en' => 'Contact Us', 'ar' => 'تواصل معنا'],
                'content' => [
                    'ar' => <<<'HTML'
                        <p>عندك سؤال عن كاش أفندي أو واجهتك مشكلة في عرض؟ يسعدنا تواصلك. اختار البريد المناسب عشان رسالتك توصل للفريق المختص.</p>
                        <h2>الاستفسارات والدعم</h2>
                        <p>للأسئلة العامة، ومشكلات الحساب، والإبلاغ عن كوبون لا يعمل أو بيانات عرض تحتاج إلى تصحيح، وطلبات الخصوصية:</p>
                        <p><a href="mailto:support@cashafandy.com"><bdi>support@cashafandy.com</bdi></a></p>
                        <p>اذكر اسم المتجر ورابط العرض ووصف المشكلة، وأرفق رسالة الخطأ أو صورة توضحها بعد إخفاء أي بيانات حساسة. إذا كانت المشكلة متعلقة بحسابك، يُفضّل مراسلتنا من البريد المسجل به.</p>
                        <h2>الشراكات والتعاون</h2>
                        <p>للمتاجر والعلامات التجارية وشبكات التسويق الراغبة في عرض كوبونات أو عروض خصم أو مناقشة فرص التعاون:</p>
                        <p><a href="mailto:partners@cashafandy.com"><bdi>partners@cashafandy.com</bdi></a></p>
                        <p>عرّفنا باسم النشاط ورابط الموقع والبلدان المستهدفة وطبيعة العرض أو التعاون المقترح، مع وسيلة مناسبة للتواصل.</p>
                        <h2>قبل إرسال رسالتك</h2>
                        <ul>
                            <li>اكتب عنوانًا واضحًا للرسالة وأرسل التفاصيل التي تساعدنا على فهم طلبك.</li>
                            <li>لا ترسل كلمة المرور أو رموز التحقق أو بيانات بطاقة الدفع.</li>
                            <li>للاستفسار عن الدفع أو الشحن أو الاسترجاع، تواصل مع المتجر الذي أتممت الشراء لديه أولًا.</li>
                        </ul>
                        HTML,
                    'en' => <<<'HTML'
                        <p>Have a question about Cash Afandy or a problem with an offer? We would be happy to hear from you. Choose the appropriate email address so your message reaches the right team.</p>
                        <h2>Enquiries and support</h2>
                        <p>For general questions, account issues, coupons that do not work, inaccurate offer details and privacy requests:</p>
                        <p><a href="mailto:support@cashafandy.com"><bdi>support@cashafandy.com</bdi></a></p>
                        <p>Include the store name, offer link and a description of the issue. Attach any error message or screenshot after hiding sensitive information. For account issues, preferably write from your registered email address.</p>
                        <h2>Partnerships</h2>
                        <p>For stores, brands and affiliate networks interested in listing coupons or discount offers, or discussing collaboration:</p>
                        <p><a href="mailto:partners@cashafandy.com"><bdi>partners@cashafandy.com</bdi></a></p>
                        <p>Tell us your business name, website, target countries and proposed offer or collaboration, along with suitable contact details.</p>
                        <h2>Before sending your message</h2>
                        <ul>
                            <li>Use a clear subject and include details that help us understand your request.</li>
                            <li>Never send passwords, verification codes or payment card details.</li>
                            <li>For payment, delivery or return enquiries, contact the store where you made the purchase first.</li>
                        </ul>
                        HTML,
                ],
            ],
            'faq' => [
                'title' => ['en' => 'FAQ', 'ar' => 'الأسئلة الشائعة'],
                'content' => [
                    'ar' => <<<'HTML'
                        <p>هنا إجابات لأهم الأسئلة عن استخدام كاش أفندي والكوبونات وعروض الخصم.</p>
                        <h2>إيه هو كاش أفندي؟</h2>
                        <p>كاش أفندي بيساعدك تكتشف كوبونات وعروض خصم لدى متاجر مختلفة، وتقارن تفاصيل العروض قبل ما تبدأ التسوق.</p>
                        <h2>هل لازم أعمل حساب عشان أتصفح العروض؟</h2>
                        <p>تقدر تتصفح المتاجر والعروض من غير تسجيل دخول. الخدمات المرتبطة بحسابك وبياناتك الشخصية بتحتاج تسجيل الدخول.</p>
                        <h2>إزاي أستخدم كوبون خصم؟</h2>
                        <p>اختار العرض واقرأ شروطه، ثم اضغط لإظهار الكود وانسخه. افتح المتجر وأضف المنتجات المؤهلة، والصق الكود في خانة الكوبون عند إتمام الطلب. اتأكد إن الخصم اتطبق قبل الدفع.</p>
                        <h2>ليه الكوبون مش شغال؟</h2>
                        <p>ممكن يكون انتهى، أو مخصص لبلد أو منتجات أو عملاء معينين، أو محتاج حد أدنى للطلب، أو ما ينفعش مع خصم تاني. راجع الشروط وتأكد من نسخ الكود من غير مسافات إضافية. لو المشكلة مستمرة، ابعت اسم المتجر ورابط العرض للدعم.</p>
                        <h2>هل العروض متاحة في كل البلاد؟</h2>
                        <p>مش بالضرورة. كل عرض ممكن يكون مرتبطًا ببلد أو متجر معين. اختار بلدك وراجع خانة التوفر وشروط العرض قبل الشراء.</p>
                        <h2>مين أتواصل معاه بخصوص طلب أو استرجاع؟</h2>
                        <p>تواصل مع المتجر بخصوص الدفع والشحن والإلغاء والاسترجاع. لو المشكلة في كود أو تفاصيل عرض منشور على كاش أفندي، راسل <a href="mailto:support@cashafandy.com"><bdi>support@cashafandy.com</bdi></a>.</p>
                        <h2>إزاي أعدّل بياناتي أو أوقف النشرة البريدية؟</h2>
                        <p>تقدر تعدّل بياناتك وتفضيلاتك من حسابك. لطلبات حذف البيانات أو إيقاف رسائل النشرة البريدية، راسل <a href="mailto:support@cashafandy.com"><bdi>support@cashafandy.com</bdi></a> من البريد المعني بالطلب.</p>
                        <h2>عندي متجر وعايز أتعاون مع كاش أفندي، أعمل إيه؟</h2>
                        <p>ابعت اسم النشاط ورابطه والبلدان المستهدفة وفكرة التعاون على <a href="mailto:partners@cashafandy.com"><bdi>partners@cashafandy.com</bdi></a>.</p>
                        HTML,
                    'en' => <<<'HTML'
                        <p>Find answers to common questions about Cash Afandy, coupons and discount offers.</p>
                        <h2>What is Cash Afandy?</h2>
                        <p>Cash Afandy helps you discover coupons and discount offers from different stores and compare offer details before shopping.</p>
                        <h2>Do I need an account to browse offers?</h2>
                        <p>You can browse stores and offers without signing in. Services linked to your account and personal details require sign-in.</p>
                        <h2>How do I use a coupon?</h2>
                        <p>Choose an offer and read its conditions, then reveal and copy the code. Visit the store, add eligible products and paste the code into the coupon field at checkout. Confirm that the discount has been applied before paying.</p>
                        <h2>Why is a coupon not working?</h2>
                        <p>It may have expired, apply only to certain countries, products or customers, require a minimum spend or exclude other discounts. Check the conditions and copy the code without extra spaces. If the issue continues, send support the store name and offer link.</p>
                        <h2>Are offers available in every country?</h2>
                        <p>Not necessarily. Offers may be limited to a particular country or store. Select your country and check availability and offer conditions before purchasing.</p>
                        <h2>Who handles order or return enquiries?</h2>
                        <p>Contact the store about payments, delivery, cancellations and returns. For issues with a code or offer information on Cash Afandy, email <a href="mailto:support@cashafandy.com"><bdi>support@cashafandy.com</bdi></a>.</p>
                        <h2>How do I edit my details or stop newsletter emails?</h2>
                        <p>You can update your details and preferences in your account. For data deletion or newsletter removal requests, email <a href="mailto:support@cashafandy.com"><bdi>support@cashafandy.com</bdi></a> from the address concerned.</p>
                        <h2>How can my store partner with Cash Afandy?</h2>
                        <p>Send your business name, website, target countries and proposed collaboration to <a href="mailto:partners@cashafandy.com"><bdi>partners@cashafandy.com</bdi></a>.</p>
                        HTML,
                ],
            ],
        ];

        foreach ($pages as $slug => $page) {
            StaticPage::updateOrCreate(
                ['slug' => $slug],
                $page
            );
        }
    }
}
