<?php

namespace Modules\AdCampaigns;

use Core\Controller;
use Core\Session;
use Core\Auth;
use Core\Security;
use Core\Router;

class AdCampaignsController extends Controller
{
    private $adCampaignsModel;

    public function __construct()
    {
        parent::__construct();
        $this->adCampaignsModel = new AdCampaignsModel();
    }

    // --- Advertiser Dashboard & Management ---

    public function dashboard()
    {
        Auth::checkLogin();
        $user_id = Session::get("user_id");
        $advertiser = $this->adCampaignsModel->getAdvertiserByUserId($user_id);

        if (!$advertiser) {
            // If no advertiser profile, redirect to create one
            Router::redirect("/ad_campaigns/create_advertiser");
        }

        $campaigns = $this->adCampaignsModel->getCampaignsByAdvertiserId($advertiser["id"]);
        $balance = $advertiser["balance"];
        $transactions = $this->adCampaignsModel->getTransactionsByAdvertiserId($advertiser["id"]);

        $this->view("ad_campaigns/dashboard", [
            "advertiser" => $advertiser,
            "campaigns" => $campaigns,
            "balance" => $balance,
            "transactions" => $transactions
        ]);
    }

    public function create_advertiser()
    {
        Auth::checkLogin();
        $user_id = Session::get("user_id");

        if ($this->adCampaignsModel->getAdvertiserByUserId($user_id)) {
            Session::set("error", "لديك بالفعل حساب معلن.");
            Router::redirect("/ad_campaigns/dashboard");
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            Security::checkCsrfToken();

            $company_name = Security::sanitizeInput($_POST["company_name"]);
            $contact_person = Security::sanitizeInput($_POST["contact_person"]);
            $email = Security::sanitizeInput($_POST["email"]);
            $phone = Security::sanitizeInput($_POST["phone"]);
            $address = Security::sanitizeInput($_POST["address"]);

            if (empty($company_name) || empty($contact_person) || empty($email)) {
                Session::set("error", "الرجاء تعبئة جميع الحقول المطلوبة.");
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Session::set("error", "صيغة البريد الإلكتروني غير صحيحة.");
            } elseif ($this->adCampaignsModel->getAdvertiserByEmail($email)) {
                Session::set("error", "البريد الإلكتروني مستخدم بالفعل.");
            } else {
                $advertiser_id = $this->adCampaignsModel->createAdvertiser(
                    $user_id, $company_name, $contact_person, $email, $phone, $address
                );
                if ($advertiser_id) {
                    Session::set("success", "تم إنشاء حساب المعلن بنجاح!");
                    Router::redirect("/ad_campaigns/dashboard");
                } else {
                    Session::set("error", "حدث خطأ أثناء إنشاء حساب المعلن.");
                }
            }
        }

        $this->view("ad_campaigns/create_advertiser");
    }

    // --- Campaign Management ---

    public function create_campaign()
    {
        Auth::checkLogin();
        $user_id = Session::get("user_id");
        $advertiser = $this->adCampaignsModel->getAdvertiserByUserId($user_id);

        if (!$advertiser) {
            Session::set("error", "الرجاء إنشاء حساب معلن أولاً.");
            Router::redirect("/ad_campaigns/create_advertiser");
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            Security::checkCsrfToken();

            $name = Security::sanitizeInput($_POST["name"]);
            $budget = (float)Security::sanitizeInput($_POST["budget"]);
            $budget_type = Security::sanitizeInput($_POST["budget_type"]);
            $cpc_rate = (float)Security::sanitizeInput($_POST["cpc_rate"]);
            $cpm_rate = (float)Security::sanitizeInput($_POST["cpm_rate"]);
            $start_date = Security::sanitizeInput($_POST["start_date"]);
            $end_date = Security::sanitizeInput($_POST["end_date"]);
            $target_countries = Security::sanitizeInput($_POST["target_countries"]);
            $target_languages = Security::sanitizeInput($_POST["target_languages"]);
            $target_keywords = Security::sanitizeInput($_POST["target_keywords"]);

            $settings = $this->adCampaignsModel->getAdSettings();

            if (empty($name) || $budget <= 0 || empty($start_date)) {
                Session::set("error", "الرجاء تعبئة جميع الحقول المطلوبة وبمبالغ صحيحة.");
            } elseif ($cpc_rate < $settings["cpc_min_bid"] || $cpm_rate < $settings["cpm_min_bid"]) {
                Session::set("error", "سعر النقرة/الألف ظهور أقل من الحد الأدنى المسموح به.");
            } else {
                $campaign_id = $this->adCampaignsModel->createCampaign(
                    $advertiser["id"], $name, $budget, $budget_type, $cpc_rate, $cpm_rate,
                    $start_date, $end_date, $target_countries, $target_languages, $target_keywords
                );
                if ($campaign_id) {
                    Session::set("success", "تم إنشاء الحملة بنجاح! سيتم مراجعتها قريباً.");
                    Router::redirect("/ad_campaigns/dashboard");
                } else {
                    Session::set("error", "حدث خطأ أثناء إنشاء الحملة.");
                }
            }
        }

        $settings = $this->adCampaignsModel->getAdSettings();
        $this->view("ad_campaigns/create_campaign", ["settings" => $settings]);
    }

    public function edit_campaign($campaign_id)
    {
        Auth::checkLogin();
        $user_id = Session::get("user_id");
        $advertiser = $this->adCampaignsModel->getAdvertiserByUserId($user_id);

        if (!$advertiser) {
            Session::set("error", "الرجاء إنشاء حساب معلن أولاً.");
            Router::redirect("/ad_campaigns/create_advertiser");
        }

        $campaign = $this->adCampaignsModel->getCampaignById($campaign_id);

        if (!$campaign || $campaign["advertiser_id"] != $advertiser["id"]) {
            Session::set("error", "الحملة غير موجودة أو لا تملك صلاحية تعديلها.");
            Router::redirect("/ad_campaigns/dashboard");
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            Security::checkCsrfToken();

            $name = Security::sanitizeInput($_POST["name"]);
            $budget = (float)Security::sanitizeInput($_POST["budget"]);
            $budget_type = Security::sanitizeInput($_POST["budget_type"]);
            $cpc_rate = (float)Security::sanitizeInput($_POST["cpc_rate"]);
            $cpm_rate = (float)Security::sanitizeInput($_POST["cpm_rate"]);
            $start_date = Security::sanitizeInput($_POST["start_date"]);
            $end_date = Security::sanitizeInput($_POST["end_date"]);
            $target_countries = Security::sanitizeInput($_POST["target_countries"]);
            $target_languages = Security::sanitizeInput($_POST["target_languages"]);
            $target_keywords = Security::sanitizeInput($_POST["target_keywords"]);

            $settings = $this->adCampaignsModel->getAdSettings();

            if (empty($name) || $budget <= 0 || empty($start_date)) {
                Session::set("error", "الرجاء تعبئة جميع الحقول المطلوبة وبمبالغ صحيحة.");
            } elseif ($cpc_rate < $settings["cpc_min_bid"] || $cpm_rate < $settings["cpm_min_bid"]) {
                Session::set("error", "سعر النقرة/الألف ظهور أقل من الحد الأدنى المسموح به.");
            } else {
                $updated = $this->adCampaignsModel->updateCampaign(
                    $campaign_id, $name, $budget, $budget_type, $cpc_rate, $cpm_rate,
                    $start_date, $end_date, $target_countries, $target_languages, $target_keywords
                );
                if ($updated) {
                    Session::set("success", "تم تحديث الحملة بنجاح!");
                    Router::redirect("/ad_campaigns/dashboard");
                } else {
                    Session::set("error", "حدث خطأ أثناء تحديث الحملة.");
                }
            }
        }

        $settings = $this->adCampaignsModel->getAdSettings();
        $this->view("ad_campaigns/edit_campaign", ["campaign" => $campaign, "settings" => $settings]);
    }

    public function pause_campaign($campaign_id)
    {
        Auth::checkLogin();
        $user_id = Session::get("user_id");
        $advertiser = $this->adCampaignsModel->getAdvertiserByUserId($user_id);

        if (!$advertiser) {
            Session::set("error", "الرجاء إنشاء حساب معلن أولاً.");
            Router::redirect("/ad_campaigns/create_advertiser");
        }

        $campaign = $this->adCampaignsModel->getCampaignById($campaign_id);

        if (!$campaign || $campaign["advertiser_id"] != $advertiser["id"]) {
            Session::set("error", "الحملة غير موجودة أو لا تملك صلاحية تعديلها.");
            Router::redirect("/ad_campaigns/dashboard");
        }

        if ($this->adCampaignsModel->updateCampaignStatus($campaign_id, "paused")) {
            Session::set("success", "تم إيقاف الحملة مؤقتاً.");
        } else {
            Session::set("error", "حدث خطأ أثناء إيقاف الحملة.");
        }
        Router::redirect("/ad_campaigns/dashboard");
    }

    public function activate_campaign($campaign_id)
    {
        Auth::checkLogin();
        $user_id = Session::get("user_id");
        $advertiser = $this->adCampaignsModel->getAdvertiserByUserId($user_id);

        if (!$advertiser) {
            Session::set("error", "الرجاء إنشاء حساب معلن أولاً.");
            Router::redirect("/ad_campaigns/create_advertiser");
        }

        $campaign = $this->adCampaignsModel->getCampaignById($campaign_id);

        if (!$campaign || $campaign["advertiser_id"] != $advertiser["id"]) {
            Session::set("error", "الحملة غير موجودة أو لا تملك صلاحية تعديلها.");
            Router::redirect("/ad_campaigns/dashboard");
        }

        if ($this->adCampaignsModel->updateCampaignStatus($campaign_id, "active")) {
            Session::set("success", "تم تفعيل الحملة بنجاح.");
        } else {
            Session::set("error", "حدث خطأ أثناء تفعيل الحملة.");
        }
        Router::redirect("/ad_campaigns/dashboard");
    }

    // --- Ad Management ---

    public function manage_ads($campaign_id)
    {
        Auth::checkLogin();
        $user_id = Session::get("user_id");
        $advertiser = $this->adCampaignsModel->getAdvertiserByUserId($user_id);

        if (!$advertiser) {
            Session::set("error", "الرجاء إنشاء حساب معلن أولاً.");
            Router::redirect("/ad_campaigns/create_advertiser");
        }

        $campaign = $this->adCampaignsModel->getCampaignById($campaign_id);

        if (!$campaign || $campaign["advertiser_id"] != $advertiser["id"]) {
            Session::set("error", "الحملة غير موجودة أو لا تملك صلاحية تعديلها.");
            Router::redirect("/ad_campaigns/dashboard");
        }

        $ads = $this->adCampaignsModel->getAdsByCampaignId($campaign_id);
        $this->view("ad_campaigns/manage_ads", ["campaign" => $campaign, "ads" => $ads]);
    }

    public function create_ad($campaign_id)
    {
        Auth::checkLogin();
        $user_id = Session::get("user_id");
        $advertiser = $this->adCampaignsModel->getAdvertiserByUserId($user_id);

        if (!$advertiser) {
            Session::set("error", "الرجاء إنشاء حساب معلن أولاً.");
            Router::redirect("/ad_campaigns/create_advertiser");
        }

        $campaign = $this->adCampaignsModel->getCampaignById($campaign_id);

        if (!$campaign || $campaign["advertiser_id"] != $advertiser["id"]) {
            Session::set("error", "الحملة غير موجودة أو لا تملك صلاحية تعديلها.");
            Router::redirect("/ad_campaigns/dashboard");
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            Security::checkCsrfToken();

            $type = Security::sanitizeInput($_POST["type"]);
            $title = Security::sanitizeInput($_POST["title"]);
            $description = Security::sanitizeInput($_POST["description"]);
            $image_url = Security::sanitizeInput($_POST["image_url"]);
            $html_content = $_POST["html_content"]; // HTML content might contain valid tags
            $destination_url = Security::sanitizeInput($_POST["destination_url"]);

            if (empty($type) || empty($destination_url)) {
                Session::set("error", "الرجاء تعبئة جميع الحقول المطلوبة.");
            } elseif (!filter_var($destination_url, FILTER_VALIDATE_URL)) {
                Session::set("error", "صيغة رابط الوجهة غير صحيحة.");
            } else {
                $ad_id = $this->adCampaignsModel->createAd(
                    $campaign_id, $type, $title, $description, $image_url, $html_content, $destination_url
                );
                if ($ad_id) {
                    Session::set("success", "تم إنشاء الإعلان بنجاح! سيتم مراجعته قريباً.");
                    Router::redirect("/ad_campaigns/manage_ads/" . $campaign_id);
                } else {
                    Session::set("error", "حدث خطأ أثناء إنشاء الإعلان.");
                }
            }
        }

        $this->view("ad_campaigns/create_ad", ["campaign" => $campaign]);
    }

    public function edit_ad($ad_id)
    {
        Auth::checkLogin();
        $user_id = Session::get("user_id");
        $advertiser = $this->adCampaignsModel->getAdvertiserByUserId($user_id);

        if (!$advertiser) {
            Session::set("error", "الرجاء إنشاء حساب معلن أولاً.");
            Router::redirect("/ad_campaigns/create_advertiser");
        }

        $ad = $this->adCampaignsModel->getAdById($ad_id);

        if (!$ad || $this->adCampaignsModel->getCampaignById($ad["campaign_id"])["advertiser_id"] != $advertiser["id"]) {
            Session::set("error", "الإعلان غير موجود أو لا تملك صلاحية تعديله.");
            Router::redirect("/ad_campaigns/dashboard");
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            Security::checkCsrfToken();

            $type = Security::sanitizeInput($_POST["type"]);
            $title = Security::sanitizeInput($_POST["title"]);
            $description = Security::sanitizeInput($_POST["description"]);
            $image_url = Security::sanitizeInput($_POST["image_url"]);
            $html_content = $_POST["html_content"];
            $destination_url = Security::sanitizeInput($_POST["destination_url"]);

            if (empty($type) || empty($destination_url)) {
                Session::set("error", "الرجاء تعبئة جميع الحقول المطلوبة.");
            } elseif (!filter_var($destination_url, FILTER_VALIDATE_URL)) {
                Session::set("error", "صيغة رابط الوجهة غير صحيحة.");
            } else {
                $updated = $this->adCampaignsModel->updateAd(
                    $ad_id, $type, $title, $description, $image_url, $html_content, $destination_url
                );
                if ($updated) {
                    Session::set("success", "تم تحديث الإعلان بنجاح!");
                    Router::redirect("/ad_campaigns/manage_ads/" . $ad["campaign_id"]);
                } else {
                    Session::set("error", "حدث خطأ أثناء تحديث الإعلان.");
                }
            }
        }

        $this->view("ad_campaigns/edit_ad", ["ad" => $ad]);
    }

    // --- Funding & Transactions ---

    public function deposit()
    {
        Auth::checkLogin();
        $user_id = Session::get("user_id");
        $advertiser = $this->adCampaignsModel->getAdvertiserByUserId($user_id);

        if (!$advertiser) {
            Session::set("error", "الرجاء إنشاء حساب معلن أولاً.");
            Router::redirect("/ad_campaigns/create_advertiser");
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            Security::checkCsrfToken();
            $amount = (float)Security::sanitizeInput($_POST["amount"]);
            $settings = $this->adCampaignsModel->getAdSettings();

            if ($amount < $settings["min_deposit"]) {
                Session::set("error", "الحد الأدنى للإيداع هو " . $settings["min_deposit"] . ".");
            } else {
                // Simulate payment gateway interaction
                // In a real application, this would integrate with a payment gateway (e.g., PayPal, Stripe)
                $transaction_id = $this->adCampaignsModel->addTransaction(
                    $advertiser["id"], "deposit", $amount, "إيداع رصيد للحملات الإعلانية"
                );
                if ($transaction_id) {
                    $this->adCampaignsModel->updateAdvertiserBalance($advertiser["id"], $amount);
                    Session::set("success", "تم إيداع المبلغ بنجاح! رصيدك الحالي: " . ($advertiser["balance"] + $amount));
                    Router::redirect("/ad_campaigns/dashboard");
                } else {
                    Session::set("error", "حدث خطأ أثناء عملية الإيداع.");
                }
            }
        }

        $settings = $this->adCampaignsModel->getAdSettings();
        $this->view("ad_campaigns/deposit", ["settings" => $settings]);
    }

    // --- Ad Serving Logic (for public facing pages) ---

    public function get_ad($placement_code)
    {
        // This method will be called by the frontend to fetch an ad for a specific placement
        header("Content-Type: application/json");
        $ad = $this->adCampaignsModel->getEligibleAdForPlacement($placement_code);

        if ($ad) {
            // Record impression (if not already recorded for this session/IP)
            $user_id = Session::get("user_id") ? Session::get("user_id") : null;
            $ip_address = $_SERVER["REMOTE_ADDR"];

            // Basic check to prevent multiple impressions from same user/IP in short period
            // More robust logic needed for production (e.g., Redis, database cache)
            $last_impression_key = "last_ad_impression_" . $ad["id"] . "_" . md5($ip_address);
            if (!Session::get($last_impression_key) || (time() - Session::get($last_impression_key) > 60)) { // 60 seconds cooldown
                $this->adCampaignsModel->recordImpression($ad["id"], $ad["campaign_id"], $user_id, $ip_address, $ad["cpm_rate"] / 1000);
                Session::set($last_impression_key, time());
            }

            echo json_encode([
                "success" => true,
                "ad" => [
                    "id" => $ad["id"],
                    "type" => $ad["type"],
                    "title" => $ad["title"],
                    "description" => $ad["description"],
                    "image_url" => $ad["image_url"],
                    "html_content" => $ad["html_content"],
                    "destination_url" => $ad["destination_url"],
                    "campaign_id" => $ad["campaign_id"]
                ]
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "No eligible ad found."]);
        }
        exit();
    }

    public function record_click($ad_id)
    {
        // This method will be called by the frontend when an ad is clicked
        header("Content-Type: application/json");
        $ad = $this->adCampaignsModel->getAdById($ad_id);

        if ($ad) {
            $campaign = $this->adCampaignsModel->getCampaignById($ad["campaign_id"]);
            $user_id = Session::get("user_id") ? Session::get("user_id") : null;
            $ip_address = $_SERVER["REMOTE_ADDR"];

            // Prevent duplicate clicks from same user/IP in short period
            $last_click_key = "last_ad_click_" . $ad["id"] . "_" . md5($ip_address);
            if (!Session::get($last_click_key) || (time() - Session::get($last_click_key) > 60)) { // 60 seconds cooldown
                $cost = $campaign["cpc_rate"];
                if ($this->adCampaignsModel->recordClick($ad["id"], $ad["campaign_id"], $user_id, $ip_address, $cost)) {
                    Session::set($last_click_key, time());
                    echo json_encode(["success" => true, "message" => "Click recorded."]);
                } else {
                    echo json_encode(["success" => false, "message" => "Failed to record click."]);
                }
            } else {
                echo json_encode(["success" => false, "message" => "Click already recorded recently."]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "Ad not found."]);
        }
        exit();
    }

    // --- Admin Panel Management ---

    public function admin_dashboard()
    {
        Auth::checkAdmin();
        $advertisers = $this->adCampaignsModel->getAllAdvertisers();
        $campaigns = $this->adCampaignsModel->getAllCampaigns();
        $ads = $this->adCampaignsModel->getAllAds();
        $settings = $this->adCampaignsModel->getAdSettings();

        $this->view("admin/ad_campaigns/dashboard", [
            "advertisers" => $advertisers,
            "campaigns" => $campaigns,
            "ads" => $ads,
            "settings" => $settings
        ]);
    }

    public function admin_manage_advertisers()
    {
        Auth::checkAdmin();
        $advertisers = $this->adCampaignsModel->getAllAdvertisers();
        $this->view("admin/ad_campaigns/manage_advertisers", ["advertisers" => $advertisers]);
    }

    public function admin_view_advertiser($advertiser_id)
    {
        Auth::checkAdmin();
        $advertiser = $this->adCampaignsModel->getAdvertiserById($advertiser_id);
        if (!$advertiser) {
            Session::set("error", "المعلن غير موجود.");
            Router::redirect("/admin/ad_campaigns/manage_advertisers");
        }
        $campaigns = $this->adCampaignsModel->getCampaignsByAdvertiserId($advertiser_id);
        $transactions = $this->adCampaignsModel->getTransactionsByAdvertiserId($advertiser_id);
        $this->view("admin/ad_campaigns/view_advertiser", [
            "advertiser" => $advertiser,
            "campaigns" => $campaigns,
            "transactions" => $transactions
        ]);
    }

    public function admin_manage_campaigns()
    {
        Auth::checkAdmin();
        $campaigns = $this->adCampaignsModel->getAllCampaigns();
        $this->view("admin/ad_campaigns/manage_campaigns", ["campaigns" => $campaigns]);
    }

    public function admin_view_campaign($campaign_id)
    {
        Auth::checkAdmin();
        $campaign = $this->adCampaignsModel->getCampaignById($campaign_id);
        if (!$campaign) {
            Session::set("error", "الحملة غير موجودة.");
            Router::redirect("/admin/ad_campaigns/manage_campaigns");
        }
        $ads = $this->adCampaignsModel->getAdsByCampaignId($campaign_id);
        $clicks = $this->adCampaignsModel->getClicksByCampaignId($campaign_id);
        $impressions = $this->adCampaignsModel->getImpressionsByCampaignId($campaign_id);
        $this->view("admin/ad_campaigns/view_campaign", [
            "campaign" => $campaign,
            "ads" => $ads,
            "clicks" => $clicks,
            "impressions" => $impressions
        ]);
    }

    public function admin_manage_ads()
    {
        Auth::checkAdmin();
        $ads = $this->adCampaignsModel->getAllAds();
        $this->view("admin/ad_campaigns/manage_ads", ["ads" => $ads]);
    }

    public function admin_review_ad($ad_id)
    {
        Auth::checkAdmin();
        $ad = $this->adCampaignsModel->getAdById($ad_id);
        if (!$ad) {
            Session::set("error", "الإعلان غير موجود.");
            Router::redirect("/admin/ad_campaigns/manage_ads");
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            Security::checkCsrfToken();
            $status = Security::sanitizeInput($_POST["status"]);
            if ($this->adCampaignsModel->updateAdStatus($ad_id, $status)) {
                Session::set("success", "تم تحديث حالة الإعلان بنجاح.");
            } else {
                Session::set("error", "حدث خطأ أثناء تحديث حالة الإعلان.");
            }
            Router::redirect("/admin/ad_campaigns/manage_ads");
        }

        $this->view("admin/ad_campaigns/review_ad", ["ad" => $ad]);
    }

    public function admin_settings()
    {
        Auth::checkAdmin();
        $settings = $this->adCampaignsModel->getAdSettings();

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            Security::checkCsrfToken();
            $cpc_min_bid = (float)Security::sanitizeInput($_POST["cpc_min_bid"]);
            $cpm_min_bid = (float)Security::sanitizeInput($_POST["cpm_min_bid"]);
            $commission_rate = (float)Security::sanitizeInput($_POST["commission_rate"]);
            $min_deposit = (float)Security::sanitizeInput($_POST["min_deposit"]);
            $min_withdrawal = (float)Security::sanitizeInput($_POST["min_withdrawal"]);
            $ad_review_required = isset($_POST["ad_review_required"]) ? 1 : 0;

            if ($this->adCampaignsModel->updateAdSettings(
                $cpc_min_bid, $cpm_min_bid, $commission_rate, $min_deposit, $min_withdrawal, $ad_review_required
            )) {
                Session::set("success", "تم تحديث إعدادات الإعلانات بنجاح.");
            } else {
                Session::set("error", "حدث خطأ أثناء تحديث إعدادات الإعلانات.");
            }
            Router::redirect("/admin/ad_campaigns/settings");
        }

        $this->view("admin/ad_campaigns/settings", ["settings" => $settings]);
    }

    public function admin_manage_placements()
    {
        Auth::checkAdmin();
        $placements = $this->adCampaignsModel->getAllAdPlacements();
        $this->view("admin/ad_campaigns/manage_placements", ["placements" => $placements]);
    }

    public function admin_create_placement()
    {
        Auth::checkAdmin();
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            Security::checkCsrfToken();
            $name = Security::sanitizeInput($_POST["name"]);
            $description = Security::sanitizeInput($_POST["description"]);
            $code = Security::sanitizeInput($_POST["code"]);
            $width = (int)Security::sanitizeInput($_POST["width"]);
            $height = (int)Security::sanitizeInput($_POST["height"]);

            if (empty($name) || empty($code)) {
                Session::set("error", "الرجاء تعبئة جميع الحقول المطلوبة.");
            } elseif ($this->adCampaignsModel->getAdPlacementByCode($code)) {
                Session::set("error", "رمز الموضع مستخدم بالفعل.");
            } else {
                if ($this->adCampaignsModel->createAdPlacement($name, $description, $code, $width, $height)) {
                    Session::set("success", "تم إنشاء موضع الإعلان بنجاح.");
                    Router::redirect("/admin/ad_campaigns/manage_placements");
                } else {
                    Session::set("error", "حدث خطأ أثناء إنشاء موضع الإعلان.");
                }
            }
        }
        $this->view("admin/ad_campaigns/create_placement");
    }

    public function admin_edit_placement($placement_id)
    {
        Auth::checkAdmin();
        $placement = $this->adCampaignsModel->getAdPlacementById($placement_id);
        if (!$placement) {
            Session::set("error", "موضع الإعلان غير موجود.");
            Router::redirect("/admin/ad_campaigns/manage_placements");
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            Security::checkCsrfToken();
            $name = Security::sanitizeInput($_POST["name"]);
            $description = Security::sanitizeInput($_POST["description"]);
            $code = Security::sanitizeInput($_POST["code"]);
            $width = (int)Security::sanitizeInput($_POST["width"]);
            $height = (int)Security::sanitizeInput($_POST["height"]);
            $status = Security::sanitizeInput($_POST["status"]);

            if (empty($name) || empty($code)) {
                Session::set("error", "الرجاء تعبئة جميع الحقول المطلوبة.");
            } elseif ($this->adCampaignsModel->getAdPlacementByCode($code) && $this->adCampaignsModel->getAdPlacementByCode($code)["id"] != $placement_id) {
                Session::set("error", "رمز الموضع مستخدم بالفعل.");
            } else {
                if ($this->adCampaignsModel->updateAdPlacement($placement_id, $name, $description, $code, $width, $height, $status)) {
                    Session::set("success", "تم تحديث موضع الإعلان بنجاح.");
                    Router::redirect("/admin/ad_campaigns/manage_placements");
                } else {
                    Session::set("error", "حدث خطأ أثناء تحديث موضع الإعلان.");
                }
            }
        }
        $this->view("admin/ad_campaigns/edit_placement", ["placement" => $placement]);
    }

    public function admin_delete_placement($placement_id)
    {
        Auth::checkAdmin();
        if ($this->adCampaignsModel->deleteAdPlacement($placement_id)) {
            Session::set("success", "تم حذف موضع الإعلان بنجاح.");
        } else {
            Session::set("error", "حدث خطأ أثناء حذف موضع الإعلان.");
        }
        Router::redirect("/admin/ad_campaigns/manage_placements");
    }

    public function admin_manage_placement_ads($placement_id)
    {
        Auth::checkAdmin();
        $placement = $this->adCampaignsModel->getAdPlacementById($placement_id);
        if (!$placement) {
            Session::set("error", "موضع الإعلان غير موجود.");
            Router::redirect("/admin/ad_campaigns/manage_placements");
        }
        $placement_ads = $this->adCampaignsModel->getAdsForPlacement($placement_id);
        $available_ads = $this->adCampaignsModel->getAllAds(); // All ads to choose from
        $this->view("admin/ad_campaigns/manage_placement_ads", [
            "placement" => $placement,
            "placement_ads" => $placement_ads,
            "available_ads" => $available_ads
        ]);
    }

    public function admin_add_ad_to_placement($placement_id)
    {
        Auth::checkAdmin();
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            Security::checkCsrfToken();
            $ad_id = (int)Security::sanitizeInput($_POST["ad_id"]);
            $priority = (int)Security::sanitizeInput($_POST["priority"]);

            if ($this->adCampaignsModel->addAdToPlacement($placement_id, $ad_id, $priority)) {
                Session::set("success", "تم إضافة الإعلان إلى الموضع بنجاح.");
            } else {
                Session::set("error", "حدث خطأ أثناء إضافة الإعلان إلى الموضع.");
            }
            Router::redirect("/admin/ad_campaigns/manage_placement_ads/" . $placement_id);
        }
        Router::redirect("/admin/ad_campaigns/manage_placements");
    }

    public function admin_remove_ad_from_placement($placement_ad_id)
    {
        Auth::checkAdmin();
        $placement_ad = $this->adCampaignsModel->getPlacementAdById($placement_ad_id);
        if (!$placement_ad) {
            Session::set("error", "الإعلان في الموضع غير موجود.");
            Router::redirect("/admin/ad_campaigns/manage_placements");
        }

        if ($this->adCampaignsModel->removeAdFromPlacement($placement_ad_id)) {
            Session::set("success", "تم إزالة الإعلان من الموضع بنجاح.");
        } else {
            Session::set("error", "حدث خطأ أثناء إزالة الإعلان من الموضع.");
        }
        Router::redirect("/admin/ad_campaigns/manage_placement_ads/" . $placement_ad["placement_id"]);
    }

}

?>

