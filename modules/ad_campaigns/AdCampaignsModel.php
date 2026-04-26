<?php

namespace Modules\AdCampaigns;

use Core\Model;
use PDO;

class AdCampaignsModel extends Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // --- Advertiser Management ---

    public function createAdvertiser($user_id, $company_name, $contact_person, $email, $phone, $address)
    {
        $stmt = $this->db->prepare("INSERT INTO ad_advertisers (user_id, company_name, contact_person, email, phone, address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $company_name, $contact_person, $email, $phone, $address]);
        return $this->db->lastInsertId();
    }

    public function getAdvertiserByUserId($user_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM ad_advertisers WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAdvertiserById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM ad_advertisers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAdvertiserByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM ad_advertisers WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateAdvertiserBalance($advertiser_id, $amount)
    {
        $stmt = $this->db->prepare("UPDATE ad_advertisers SET balance = balance + ? WHERE id = ?");
        return $stmt->execute([$amount, $advertiser_id]);
    }

    public function getAllAdvertisers()
    {
        $stmt = $this->db->query("SELECT * FROM ad_advertisers");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- Campaign Management ---

    public function createCampaign($advertiser_id, $name, $budget, $budget_type, $cpc_rate, $cpm_rate, $start_date, $end_date, $target_countries, $target_languages, $target_keywords)
    {
        $stmt = $this->db->prepare("INSERT INTO ad_campaigns (advertiser_id, name, budget, budget_type, cpc_rate, cpm_rate, start_date, end_date, target_countries, target_languages, target_keywords) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$advertiser_id, $name, $budget, $budget_type, $cpc_rate, $cpm_rate, $start_date, $end_date, $target_countries, $target_languages, $target_keywords]);
        return $this->db->lastInsertId();
    }

    public function getCampaignById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM ad_campaigns WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getCampaignsByAdvertiserId($advertiser_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM ad_campaigns WHERE advertiser_id = ? ORDER BY created_at DESC");
        $stmt->execute([$advertiser_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateCampaign($id, $name, $budget, $budget_type, $cpc_rate, $cpm_rate, $start_date, $end_date, $target_countries, $target_languages, $target_keywords)
    {
        $stmt = $this->db->prepare("UPDATE ad_campaigns SET name = ?, budget = ?, budget_type = ?, cpc_rate = ?, cpm_rate = ?, start_date = ?, end_date = ?, target_countries = ?, target_languages = ?, target_keywords = ? WHERE id = ?");
        return $stmt->execute([$name, $budget, $budget_type, $cpc_rate, $cpm_rate, $start_date, $end_date, $target_countries, $target_languages, $target_keywords, $id]);
    }

    public function updateCampaignStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE ad_campaigns SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function getAllCampaigns()
    {
        $stmt = $this->db->query("SELECT ac.*, aa.company_name FROM ad_campaigns ac JOIN ad_advertisers aa ON ac.advertiser_id = aa.id ORDER BY ac.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- Ad Management ---

    public function createAd($campaign_id, $type, $title, $description, $image_url, $html_content, $destination_url)
    {
        $stmt = $this->db->prepare("INSERT INTO ad_ads (campaign_id, type, title, description, image_url, html_content, destination_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$campaign_id, $type, $title, $description, $image_url, $html_content, $destination_url]);
        return $this->db->lastInsertId();
    }

    public function getAdById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM ad_ads WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAdsByCampaignId($campaign_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM ad_ads WHERE campaign_id = ? ORDER BY created_at DESC");
        $stmt->execute([$campaign_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateAd($id, $type, $title, $description, $image_url, $html_content, $destination_url)
    {
        $stmt = $this->db->prepare("UPDATE ad_ads SET type = ?, title = ?, description = ?, image_url = ?, html_content = ?, destination_url = ? WHERE id = ?");
        return $stmt->execute([$type, $title, $description, $image_url, $html_content, $destination_url, $id]);
    }

    public function updateAdStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE ad_ads SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function getAllAds()
    {
        $stmt = $this->db->query("SELECT aa.*, ac.name as campaign_name, ad.company_name as advertiser_name FROM ad_ads aa JOIN ad_campaigns ac ON aa.campaign_id = ac.id JOIN ad_advertisers ad ON ac.advertiser_id = ad.id ORDER BY aa.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- Transactions ---

    public function addTransaction($advertiser_id, $type, $amount, $description)
    {
        $stmt = $this->db->prepare("INSERT INTO ad_transactions (advertiser_id, type, amount, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$advertiser_id, $type, $amount, $description]);
        return $this->db->lastInsertId();
    }

    public function getTransactionsByAdvertiserId($advertiser_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM ad_transactions WHERE advertiser_id = ? ORDER BY transaction_time DESC");
        $stmt->execute([$advertiser_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- Ad Serving Logic ---

    public function getEligibleAdForPlacement($placement_code)
    {
        // Get placement details
        $placement = $this->getAdPlacementByCode($placement_code);
        if (!$placement || $placement["status"] != "active") {
            return null;
        }

        // Get ads associated with this placement, ordered by priority
        $stmt = $this->db->prepare("
            SELECT 
                a.*, 
                c.cpc_rate, 
                c.cpm_rate, 
                c.budget, 
                c.budget_type, 
                c.current_spend, 
                c.start_date, 
                c.end_date, 
                c.target_countries, 
                c.target_languages, 
                c.target_keywords
            FROM ad_ads a
            JOIN ad_campaigns c ON a.campaign_id = c.id
            JOIN ad_placement_ads pa ON a.id = pa.ad_id
            WHERE pa.placement_id = ? 
            AND pa.status = 'active'
            AND a.status = 'active'
            AND c.status = 'active'
            AND c.start_date <= CURDATE()
            AND (c.end_date IS NULL OR c.end_date >= CURDATE())
            ORDER BY pa.priority DESC, RAND()
            LIMIT 1
        ");
        $stmt->execute([$placement["id"]]);
        $ad = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ad) {
            // Check budget and targeting (simplified for example)
            if ($ad["budget_type"] == "total" && $ad["current_spend"] >= $ad["budget"]) {
                return null; // Campaign budget exhausted
            }
            // More complex targeting logic (countries, languages, keywords) would go here
            return $ad;
        }
        return null;
    }

    public function recordImpression($ad_id, $campaign_id, $user_id, $ip_address, $cost)
    {
        $stmt = $this->db->prepare("INSERT INTO ad_impressions (ad_id, campaign_id, user_id, ip_address, cost) VALUES (?, ?, ?, ?, ?)");
        $executed = $stmt->execute([$ad_id, $campaign_id, $user_id, $ip_address, $cost]);
        if ($executed) {
            // Update ad and campaign impression counts
            $this->db->prepare("UPDATE ad_ads SET impressions = impressions + 1 WHERE id = ?")->execute([$ad_id]);
            $this->db->prepare("UPDATE ad_campaigns SET total_impressions = total_impressions + 1 WHERE id = ?")->execute([$campaign_id]);
            return true;
        }
        return false;
    }

    public function recordClick($ad_id, $campaign_id, $user_id, $ip_address, $cost)
    {
        $stmt = $this->db->prepare("INSERT INTO ad_clicks (ad_id, campaign_id, user_id, ip_address, cost) VALUES (?, ?, ?, ?, ?)");
        $executed = $stmt->execute([$ad_id, $campaign_id, $user_id, $ip_address, $cost]);
        if ($executed) {
            // Update ad and campaign click counts and spend
            $this->db->prepare("UPDATE ad_ads SET clicks = clicks + 1 WHERE id = ?")->execute([$ad_id]);
            $this->db->prepare("UPDATE ad_campaigns SET total_clicks = total_clicks + 1, current_spend = current_spend + ? WHERE id = ?")->execute([$cost, $campaign_id]);
            // Deduct from advertiser balance
            $campaign = $this->getCampaignById($campaign_id);
            if ($campaign) {
                $this->updateAdvertiserBalance($campaign["advertiser_id"], -$cost);
                $this->addTransaction($campaign["advertiser_id"], "campaign_spend", $cost, "تكلفة نقرة إعلان: " . $ad_id);
            }
            return true;
        }
        return false;
    }

    public function getClicksByCampaignId($campaign_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM ad_clicks WHERE campaign_id = ? ORDER BY click_time DESC");
        $stmt->execute([$campaign_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getImpressionsByCampaignId($campaign_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM ad_impressions WHERE campaign_id = ? ORDER BY impression_time DESC");
        $stmt->execute([$campaign_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- Settings Management ---

    public function getAdSettings()
    {
        $stmt = $this->db->query("SELECT * FROM ad_settings LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateAdSettings($cpc_min_bid, $cpm_min_bid, $commission_rate, $min_deposit, $min_withdrawal, $ad_review_required)
    {
        $stmt = $this->db->prepare("UPDATE ad_settings SET cpc_min_bid = ?, cpm_min_bid = ?, commission_rate = ?, min_deposit = ?, min_withdrawal = ?, ad_review_required = ? WHERE id = 1");
        return $stmt->execute([$cpc_min_bid, $cpm_min_bid, $commission_rate, $min_deposit, $min_withdrawal, $ad_review_required]);
    }

    // --- Ad Placements Management ---

    public function createAdPlacement($name, $description, $code, $width, $height)
    {
        $stmt = $this->db->prepare("INSERT INTO ad_placements (name, description, code, width, height) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $code, $width, $height]);
        return $this->db->lastInsertId();
    }

    public function getAdPlacementById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM ad_placements WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAdPlacementByCode($code)
    {
        $stmt = $this->db->prepare("SELECT * FROM ad_placements WHERE code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateAdPlacement($id, $name, $description, $code, $width, $height, $status)
    {
        $stmt = $this->db->prepare("UPDATE ad_placements SET name = ?, description = ?, code = ?, width = ?, height = ?, status = ? WHERE id = ?");
        return $stmt->execute([$name, $description, $code, $width, $height, $status, $id]);
    }

    public function deleteAdPlacement($id)
    {
        $stmt = $this->db->prepare("DELETE FROM ad_placements WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getAllAdPlacements()
    {
        $stmt = $this->db->query("SELECT * FROM ad_placements ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addAdToPlacement($placement_id, $ad_id, $priority)
    {
        $stmt = $this->db->prepare("INSERT INTO ad_placement_ads (placement_id, ad_id, priority) VALUES (?, ?, ?)");
        return $stmt->execute([$placement_id, $ad_id, $priority]);
    }

    public function removeAdFromPlacement($placement_ad_id)
    {
        $stmt = $this->db->prepare("DELETE FROM ad_placement_ads WHERE id = ?");
        return $stmt->execute([$placement_ad_id]);
    }

    public function getAdsForPlacement($placement_id)
    {
        $stmt = $this->db->prepare("SELECT apa.*, a.title, a.type, a.image_url, a.destination_url FROM ad_placement_ads apa JOIN ad_ads a ON apa.ad_id = a.id WHERE apa.placement_id = ? ORDER BY apa.priority DESC");
        $stmt->execute([$placement_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPlacementAdById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM ad_placement_ads WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

}

?>

