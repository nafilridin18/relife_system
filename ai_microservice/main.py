"""
ReliefX AI Microservice (v3.0)
FastAPI Backend for Predictive Flood Risk, Geo-Urgency Dispatch, 
Spatial SOS Clustering (DBSCAN simulation), and Citizen Helpline Chatbot.
"""

from fastapi import FastAPI, Query, HTTPException, status
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from typing import List, Optional, Dict, Any
from datetime import datetime
import re
import math
import random
import os

app = FastAPI(
    title="ReliefX AI Microservice",
    description="Predictive AI/ML API for Disaster Relief Management (Flood Risk, SOS Spatial Clustering, Geo-Urgency, Helpline NLP)",
    version="3.0.0"
)

# Enable CORS for monolithic PHP frontend and citizen portal
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# ============================================================================
# PYDANTIC DATA MODELS
# ============================================================================

class FloodPredictionResponse(BaseModel):
    latitude: float
    longitude: float
    zone_name: str
    risk_level: str  # "Low", "Moderate", "Severe", "Critical"
    risk_score: float # 0 - 100
    water_level_m: float
    danger_level_m: float
    delta_above_danger_m: float
    rainfall_forecast_24h_mm: float
    upstream_discharge_cusec: int
    trend_48h: str
    recommended_action: str
    confidence_score: float
    timestamp: str

class DispatchRecommendation(BaseModel):
    rank: int
    zone_id: int
    zone_name: str
    district: str
    urgency_index: float
    flood_risk_score: float
    sos_density_score: float
    underserved_factor: float
    nearest_warehouse: str
    transit_time_est: str
    recommended_stock: List[Dict[str, Any]]
    priority_level: str # "CRITICAL", "HIGH", "MODERATE"

class GeoUrgencyResponse(BaseModel):
    urgency_formula: str
    calculated_at: str
    total_active_zones: int
    critical_zones_count: int
    warehouse_dispatch_queue: List[DispatchRecommendation]

class ChatWebhookRequest(BaseModel):
    message: str = Field(..., description="User message in English or Bengali")
    session_id: Optional[str] = "session-default"
    language: Optional[str] = "auto"
    user_location: Optional[Dict[str, float]] = None

class RescueTicket(BaseModel):
    ticket_id: str
    status: str # "DISPATCHED", "PENDING_TRIAGE", "RESOLVED"
    priority: str # "CRITICAL", "HIGH", "NORMAL"
    beneficiary_name: Optional[str]
    detected_gps: Optional[str]
    detected_landmark: Optional[str]
    immediate_needs: List[str]
    assigned_unit: str
    timestamp: str

class ChatWebhookResponse(BaseModel):
    reply: str
    is_emergency: bool
    ticket: Optional[RescueTicket] = None
    suggested_actions: List[str]

class SosSubmissionRequest(BaseModel):
    name: str = Field(..., description="Beneficiary or Reporter Name")
    phone: str = Field(..., description="Contact Mobile Number")
    lat: float = Field(..., description="GPS Latitude")
    lon: float = Field(..., description="GPS Longitude")
    landmark: str = Field(..., description="Surrounding landmark or location details")
    people_count: int = Field(default=1, ge=1, description="Number of trapped or affected people")
    urgency_level: str = Field(default="critical", description="User self-reported urgency")
    needs: List[str] = Field(default_factory=list, description="Immediate needs e.g. medical, food, boat, shelter")

class RescueNodeCluster(BaseModel):
    cluster_id: str
    cluster_name: str
    total_people_in_node: int
    active_sos_requests: int
    centroid_lat: float
    centroid_lon: float
    cluster_radius_meters: float
    clustering_algorithm: str # "DBSCAN (eps=50m, min_samples=2)"
    dispatch_priority: str
    assigned_rescue_team: str
    estimated_reach_time: str
    status: str

class SosSubmissionResponse(BaseModel):
    status: str
    message: str
    sos_id: str
    timestamp: str
    submitted_data: Dict[str, Any]
    rescue_node: RescueNodeCluster


# ============================================================================
# REFERENCE MOCK DATA
# ============================================================================

REFERENCE_ZONES = [
    {"id": 1, "name": "সুনামগঞ্জ সদর", "name_en": "Sunamganj Sadar", "district": "Sunamganj", "lat": 25.0658, "lon": 91.4073, "risk_base": 88.5, "danger_m": 12.8, "water_m": 14.1},
    {"id": 2, "name": "কুড়িগ্রাম চর", "name_en": "Kurigram Char", "district": "Kurigram", "lat": 25.8073, "lon": 89.6295, "risk_base": 95.0, "danger_m": 13.5, "water_m": 15.2},
    {"id": 3, "name": "গাইবান্ধা উত্তর", "name_en": "Gaibandha North", "district": "Gaibandha", "lat": 25.3297, "lon": 89.5430, "risk_base": 67.0, "danger_m": 11.2, "water_m": 11.7},
    {"id": 4, "name": "সিলেট কোম্পানীগঞ্জ", "name_en": "Sylhet Companiganj", "district": "Sylhet", "lat": 25.0815, "lon": 91.7523, "risk_base": 84.0, "danger_m": 13.0, "water_m": 13.9},
    {"id": 5, "name": "নেত্রকোণা দুর্গাপুর", "name_en": "Netrokona Durgapur", "district": "Netrokona", "lat": 25.1256, "lon": 90.6875, "risk_base": 71.5, "danger_m": 10.5, "water_m": 11.1},
    {"id": 6, "name": "জামালপুর ইসলামপুর", "name_en": "Jamalpur Islampur", "district": "Jamalpur", "lat": 25.0833, "lon": 89.7833, "risk_base": 78.0, "danger_m": 14.0, "water_m": 14.8},
    {"id": 7, "name": "বগুড়া সারিয়াকান্দি", "name_en": "Bogra Sariakandi", "district": "Bogra", "lat": 24.8833, "lon": 89.6167, "risk_base": 55.0, "danger_m": 12.0, "water_m": 11.9},
    {"id": 8, "name": "রংপুর কাউনিয়া", "name_en": "Rangpur Kaunia", "district": "Rangpur", "lat": 25.7725, "lon": 89.4183, "risk_base": 62.0, "danger_m": 13.2, "water_m": 13.4},
]


# ============================================================================
# API ENDPOINTS
# ============================================================================

@app.get("/")
def root():
    return {
        "service": "ReliefX AI Microservice",
        "version": "3.0.0",
        "status": "online",
        "docs": "/docs",
        "endpoints": [
            "GET /api/v1/flood-prediction",
            "GET /api/v1/geo-urgency",
            "POST /api/v1/chat/webhook",
            "POST /api/v1/sos-request",
            "GET /health"
        ]
    }

@app.get("/health")
def health_check():
    return {"status": "healthy", "timestamp": datetime.utcnow().isoformat() + "Z"}


# ----------------------------------------------------------------------------
# 1. FLOOD PREDICTION ENDPOINT
# ----------------------------------------------------------------------------
@app.get("/api/v1/flood-prediction", response_model=FloodPredictionResponse)
def get_flood_prediction(
    lat: float = Query(25.8073, description="Latitude coordinate"),
    lon: float = Query(89.6295, description="Longitude coordinate"),
    zone_id: Optional[int] = Query(None, description="Optional Zone ID")
):
    """
    Accepts latitude and longitude coordinates and returns an AI-modeled mock
    flood risk prediction (XGBoost/LSTM surrogate), hydrological status, and recommended actions.
    """
    # Find closest reference zone or use default
    matched_zone = None
    min_dist = float('inf')
    for z in REFERENCE_ZONES:
        if zone_id and z["id"] == zone_id:
            matched_zone = z
            break
        d = math.hypot(z["lat"] - lat, z["lon"] - lon)
        if d < min_dist:
            min_dist = d
            matched_zone = z

    if not matched_zone:
        matched_zone = REFERENCE_ZONES[1] # Kurigram Char

    risk_score = round(min(100.0, max(15.0, matched_zone["risk_base"] + (lat % 0.05) * 40)), 1)
    
    if risk_score >= 85.0:
        risk_level = "Critical"
        trend = "Rising rapidly (+0.45m/24h) — Surpassing 20-year flood levels"
        action = "Immediate evacuation of low-lying chars. Deploy army rescue speedboats and stock dry rations."
        rainfall = round(110.0 + random.uniform(10, 45), 1)
        discharge = 82400
    elif risk_score >= 70.0:
        risk_level = "Severe"
        trend = "Rising steadily (+0.25m/24h) — Danger level breached"
        action = "Mobilize community cyclone/flood shelters and dispatch saline & emergency water tablets."
        rainfall = round(70.0 + random.uniform(5, 25), 1)
        discharge = 68500
    elif risk_score >= 45.0:
        risk_level = "Moderate"
        trend = "Fluctuating near danger mark (±0.08m/24h)"
        action = "Reinforce embankment monitoring and pre-position relief kits at sub-district depots."
        rainfall = round(35.0 + random.uniform(0, 15), 1)
        discharge = 51200
    else:
        risk_level = "Low"
        trend = "Receding gradually (-0.15m/24h)"
        action = "Standard routine observation; no emergency evacuation required."
        rainfall = round(12.0 + random.uniform(0, 8), 1)
        discharge = 34000

    danger_level = matched_zone["danger_m"]
    water_level = round(matched_zone["water_m"] + (risk_score - matched_zone["risk_base"]) * 0.02, 2)
    delta = round(water_level - danger_level, 2)

    return FloodPredictionResponse(
        latitude=lat,
        longitude=lon,
        zone_name=f"{matched_zone['name']} ({matched_zone['name_en']})",
        risk_level=risk_level,
        risk_score=risk_score,
        water_level_m=water_level,
        danger_level_m=danger_level,
        delta_above_danger_m=delta,
        rainfall_forecast_24h_mm=rainfall,
        upstream_discharge_cusec=discharge,
        trend_48h=trend,
        recommended_action=action,
        confidence_score=0.94,
        timestamp=datetime.utcnow().isoformat() + "Z"
    )


# ----------------------------------------------------------------------------
# 2. GEO-URGENCY & WAREHOUSE DISPATCH ENDPOINT
# ----------------------------------------------------------------------------
@app.get("/api/v1/geo-urgency", response_model=GeoUrgencyResponse)
def get_geo_urgency():
    """
    Returns the composite Urgency Index calculated using:
    Urgency Index = (0.45 * Mock Flood Risk) + (0.35 * SOS Density) + (0.20 * Underserved Factor)
    along with an AI-prioritized NGO warehouse dispatch list.
    """
    # Mock pre-computed indicators across regional relief zones
    mock_data = [
        {
            "zone_id": 2, "name": "কুড়িগ্রাম চর", "name_en": "Kurigram Char", "district": "Kurigram",
            "flood_risk": 95.0, "sos_density": 92.0, "underserved": 96.5,
            "warehouse": "Rangpur Regional Logistics Depot", "transit": "1 hr 15 mins",
            "stock": [
                {"item": "High-Calorie Dry Food Pack", "qty": 1200, "unit": "packet", "category": "Food"},
                {"item": "Water Purification Strips", "qty": 2500, "unit": "strips", "category": "Medical"},
                {"item": "Inflatable Emergency Raft / Boat", "qty": 4, "unit": "unit", "category": "Shelter"}
            ]
        },
        {
            "zone_id": 1, "name": "সুনামগঞ্জ সদর ও হাওর", "name_en": "Sunamganj Sadar & Haor", "district": "Sunamganj",
            "flood_risk": 88.5, "sos_density": 86.0, "underserved": 91.0,
            "warehouse": "Sylhet Divisional Hub", "transit": "1 hr 45 mins",
            "stock": [
                {"item": "Drinking Water (5L)", "qty": 1800, "unit": "bottles", "category": "Food"},
                {"item": "Oral Rehydration Salts (ORS)", "qty": 3000, "unit": "packet", "category": "Medical"},
                {"item": "Heavy Tarpaulin Sheeting", "qty": 600, "unit": "piece", "category": "Shelter"}
            ]
        },
        {
            "zone_id": 6, "name": "জামালপুর ইসলামপুর", "name_en": "Jamalpur Islampur", "district": "Jamalpur",
            "flood_risk": 78.0, "sos_density": 74.0, "underserved": 79.0,
            "warehouse": "Mymensingh Central Depot", "transit": "2 hrs 10 mins",
            "stock": [
                {"item": "Rice & Lentils Family Pack", "qty": 850, "unit": "bag (10kg)", "category": "Food"},
                {"item": "First Aid & Anti-Septic Kits", "qty": 400, "unit": "box", "category": "Medical"},
                {"item": "Emergency Torches & Batteries", "qty": 350, "unit": "piece", "category": "Shelter"}
            ]
        },
        {
            "zone_id": 4, "name": "সিলেট কোম্পানীগঞ্জ", "name_en": "Sylhet Companiganj", "district": "Sylhet",
            "flood_risk": 84.0, "sos_density": 71.0, "underserved": 72.0,
            "warehouse": "Sylhet Divisional Hub", "transit": "1 hr 05 mins",
            "stock": [
                {"item": "Baby Powdered Milk & Cereal", "qty": 500, "unit": "packet", "category": "Food"},
                {"item": "Sanitary & Hygiene Kits", "qty": 750, "unit": "packet", "category": "Medical"}
            ]
        },
        {
            "zone_id": 3, "name": "গাইবান্ধা উত্তর", "name_en": "Gaibandha North", "district": "Gaibandha",
            "flood_risk": 67.0, "sos_density": 62.0, "underserved": 69.5,
            "warehouse": "Bogra Buffer Warehouse", "transit": "1 hr 50 mins",
            "stock": [
                {"item": "Fortified Biscuits & Chira", "qty": 900, "unit": "packet", "category": "Food"},
                {"item": "Mosquito Nets", "qty": 450, "unit": "piece", "category": "Shelter"}
            ]
        },
        {
            "zone_id": 5, "name": "নেত্রকোণা দুর্গাপুর", "name_en": "Netrokona Durgapur", "district": "Netrokona",
            "flood_risk": 71.5, "sos_density": 58.0, "underserved": 64.0,
            "warehouse": "Mymensingh Central Depot", "transit": "2 hrs 30 mins",
            "stock": [
                {"item": "Dry Rations Pack", "qty": 600, "unit": "packet", "category": "Food"},
                {"item": "Water Purification Strips", "qty": 1500, "unit": "strips", "category": "Medical"}
            ]
        }
    ]

    # Calculate Urgency Index = (0.45 * Flood_Risk) + (0.35 * SOS_Density) + (0.20 * Underserved_Factor)
    calculated_queue = []
    for item in mock_data:
        urgency = round(
            (0.45 * item["flood_risk"]) +
            (0.35 * item["sos_density"]) +
            (0.20 * item["underserved"]),
            1
        )
        if urgency >= 85.0:
            priority = "CRITICAL"
        elif urgency >= 72.0:
            priority = "HIGH"
        else:
            priority = "MODERATE"

        calculated_queue.append({
            "zone_id": item["zone_id"],
            "zone_name": f"{item['name']} ({item['name_en']})",
            "district": item["district"],
            "urgency_index": urgency,
            "flood_risk_score": item["flood_risk"],
            "sos_density_score": item["sos_density"],
            "underserved_factor": item["underserved"],
            "nearest_warehouse": item["warehouse"],
            "transit_time_est": item["transit"],
            "recommended_stock": item["stock"],
            "priority_level": priority
        })

    # Sort descending by Urgency Index
    calculated_queue.sort(key=lambda x: x["urgency_index"], reverse=True)
    
    # Assign ranks
    dispatch_list = []
    for idx, item in enumerate(calculated_queue, start=1):
        dispatch_list.append(DispatchRecommendation(rank=idx, **item))

    critical_count = sum(1 for x in dispatch_list if x.priority_level == "CRITICAL")

    return GeoUrgencyResponse(
        urgency_formula="Urgency Index = (0.45 * Flood_Risk) + (0.35 * SOS_Density) + (0.20 * Underserved_Factor)",
        calculated_at=datetime.utcnow().isoformat() + "Z",
        total_active_zones=len(dispatch_list),
        critical_zones_count=critical_count,
        warehouse_dispatch_queue=dispatch_list
    )


# ----------------------------------------------------------------------------
# 3. AI HELPLINE CHATBOT WEBHOOK
# ----------------------------------------------------------------------------
@app.post("/api/v1/chat/webhook", response_model=ChatWebhookResponse)
def chat_webhook(req: ChatWebhookRequest):
    """
    Accepts conversational citizen input in Bengali or English.
    Handles relief eligibility rules, 5-day emergency protocol, weather & river stages,
    citizen registration, emergency hotlines, and extracts distress entities for rescue tickets.
    """
    text = req.message.strip()
    is_bn = any('\u0980' <= char <= '\u09FF' for char in text)
    lower_text = text.lower()

    # Distinguish information queries from actual physical distress
    is_info_query = any(k in lower_text or k in text for k in [
        "কীভাবে", "কী", "কেমন", "নিয়ম", "নিয়ম", "নম্বর", "নাম্বার", "ফোন", "শর্ত", "তথ্য", "খুলব", "আবেদন",
        "how", "what", "rule", "rules", "number", "numbers", "hotline", "phone", "info", "status", "check"
    ])

    # 1. Genuine Emergency SOS Evacuation Trigger
    emergency_evac_keywords = [
        "বাঁচাও", "বাচাও", "আটকা পড়েছি", "আটকা পড়েছি", "আটকা", "উদ্ধার করুন", "উদ্ধার চাই", "ছাদে আটকা", 
        "পানিতে আটকা", "ডুবছে", "তলিয়ে গেছে", "তলিয়ে গেছে", "নৌকা পাঠান", "স্পিডবোট", "খাবার নেই", 
        "rescue", "trapped", "drowning", "roof", "water rising rapidly", "send boat", "stranded"
    ]
    is_emergency = any(kw in lower_text or kw in text for kw in emergency_evac_keywords) and not (
        ("যোগ্যতা" in text or "খুলব" in text or "নম্বর" in text or "হটলাইন" in text or "আবহাওয়া" in text) and is_info_query
    )

    ticket = None
    if is_emergency:
        # 1. Entity Extraction: Name
        name = None
        name_patterns = [
            r'(?:নাম|my name is|i am|name is|iam)\s*[:=\-]?\s*([A-Za-z\u0980-\u09FF\s]{2,25})',
            r'([A-Za-z\u0980-\u09FF\s]{2,20})\s*(?:বলছি|speaking|here)',
        ]
        for p in name_patterns:
            m = re.search(p, text, re.IGNORECASE)
            if m:
                candidate = m.group(1).strip()
                if len(candidate) > 2 and candidate.lower() not in ["rescue", "help", "emergency", "sos"]:
                    name = candidate
                    break
        if not name:
            name = "নাগরিক (Citizen In Need)" if is_bn else "Citizen In Need"

        # 2. Entity Extraction: GPS Coordinates
        detected_gps = None
        gps_match = re.search(r'([2-3]\d\.\d{3,6})[,\s]+([8-9]\d\.\d{3,6})', text)
        if gps_match:
            detected_gps = f"{gps_match.group(1)}, {gps_match.group(2)}"
        elif req.user_location and "lat" in req.user_location and "lon" in req.user_location:
            detected_gps = f"{req.user_location['lat']}, {req.user_location['lon']}"
        else:
            detected_gps = "25.8073, 89.6295 (Estimated Chilmari Ghat)"

        # 3. Entity Extraction: Landmark
        landmark = None
        landmark_keywords = [
            "স্কুল", "বিদ্যালয়", "বাঁধ", "ঘাট", "ছাদ", "মসজিদ", "মাদ্রাসা", "ব্রিজ", "পোল", "ইউনিয়ন পরিষদ",
            "school", "roof", "bridge", "ghat", "river", "mosque", "embankment", "parishad", "hospital"
        ]
        for lm in landmark_keywords:
            if lm in lower_text or lm in text:
                idx = text.lower().find(lm)
                start = max(0, idx - 15)
                end = min(len(text), idx + 35)
                landmark = text[start:end].strip()
                break
        if not landmark:
            landmark = "কাছের সাইক্লোন শেল্টার বা নদীর বাঁধ (Nearby embankment/shelter)" if is_bn else "Near river embankment/shelter"

        # 4. Needs
        needs = []
        if any(k in lower_text or k in text for k in ["ঔষধ", "ডাক্তার", "মেডিকেল", "অসুস্থ", "medical", "doctor", "medicine"]):
            needs.append("Medical Aid")
        if any(k in lower_text or k in text for k in ["নৌকা", "স্পিডবোট", "আটকা", "boat", "speed boat", "evacuate"]):
            needs.append("Rescue Boat")
        if any(k in lower_text or k in text for k in ["খাবার", "পানি", "খিদে", "food", "water", "rice"]):
            needs.append("Emergency Rations & Water")
        if not needs:
            needs = ["Evacuation & High-Energy Food"]

        ticket_id = f"TKT-2026-{random.randint(4000, 9999)}"
        ticket = RescueTicket(
            ticket_id=ticket_id,
            status="DISPATCHED",
            priority="CRITICAL",
            beneficiary_name=name,
            detected_gps=detected_gps,
            detected_landmark=landmark,
            immediate_needs=needs,
            assigned_unit="বাংলাদেশ ফায়ার সার্ভিস ও সেনা উদ্ধার ইউনিট-৩ (Bangladesh Armed Forces Unit #3)",
            timestamp=datetime.utcnow().strftime("%d %b %Y, %I:%M %p UTC")
        )
        if is_bn:
            reply = (
                f"🚨 **জরুরি উদ্ধার টিকেট খোলা হয়েছে (#{ticket_id})!**\n\n"
                f"ধৈর্য ধরুন **{name}**, আপনার উদ্ধার বার্তাটি সরাসরি দুর্যোগ কমান্ড সেন্টারে পাঠানো হয়েছে।\n"
                f"📍 **শনাক্তকৃত অবস্থান:** {landmark} (GPS: `{detected_gps}`)\n"
                f"🛥️ **নিয়োজিত ইউনিট:** ফায়ার সার্ভিস ও সেনা উদ্ধারকারী দল।\n"
                f"📞 তাত্ক্ষণিক সাহায্যের জন্য এখনই ডায়াল করুন: **৯৯৯ (জাতীয় জরুরি সেবা)** অথবা **১০৯০ (দুর্যোগ সেল)**।"
            )
        else:
            reply = (
                f"🚨 **Emergency Rescue Ticket Created (#{ticket_id})!**\n\n"
                f"Stay calm **{name}**, your distress request is dispatched to the Disaster Command Center.\n"
                f"📍 **Detected Location:** {landmark} (GPS: `{detected_gps}`)\n"
                f"🛥️ **Assigned Unit:** Fire Service & Armed Forces Rescue Team.\n"
                f"📞 Immediate hotlines: Dial **999** (National Emergency) or **1090** (Disaster Warning)."
            )
        suggestions = ["📍 Send Updated GPS", "🚨 Call 999 Directly", "📦 Check Relief Distribution"]

    # 2. Frequent Question: Relief Eligibility & 7-Day Cooldown Rule
    elif any(k in lower_text or k in text for k in ["যোগ্যতা", "কবে পাব", "৭ দিন", "৭ দিনের", "eligibility", "rule", "cooldown", "ডুপ্লিকেট"]):
        if is_bn:
            reply = (
                "📦 **ত্রাণ পাওয়ার যোগ্যতা ও ৭ দিনের নিয়ম:**\n\n"
                "১. **স্বচ্ছ বিতরণ ও ডুপ্লিকেট রোধ:** ReliefX সিস্টেমে প্রতিটি পরিবার একটি নির্দিষ্ট ক্যাটাগরিতে (যেমন খাদ্যসামগ্রী, জরুরি ওষুধ, বা বস্ত্র) ত্রাণ পাওয়ার পর **পরবর্তী ৭ দিন** ওই ক্যাটাগরির জন্য অপেক্ষমাণ থাকে।\n"
                "২. **ডাটাবেস সুরক্ষা:** ৭ দিনের মধ্যে পুনরায় একই ক্যাটাগরিতে ত্রাণ নেওয়ার চেষ্টা করা হলে ডাটাবেসের ইউনিক কনস্ট্রেইন্ট তা ব্লক করে এবং অডিট লগে সংরক্ষণ করে।\n"
                "৩. **যোগ্যতা যাচাই করার উপায়:** আপনার **Citizen একাউন্টে** লগইন করুন। ড্যাশবোর্ডে আপনি পূর্বে কী কী ত্রাণ পেয়েছেন, কোন এনজিও দিয়েছে এবং পরবর্তী ত্রাণ পাওয়ার তারিখ স্পষ্টভাবে দেখতে পাবেন।"
            )
        else:
            reply = (
                "📦 **Relief Eligibility & The 7-Day Rule:**\n\n"
                "1. **Fair Allocation & Duplicate Prevention:** In ReliefX, after receiving relief in a category (Food, Medical, Shelter), a family enters a **7-day cooldown** for that category.\n"
                "2. **Automatic Guard:** Attempts to claim relief within 7 days are automatically rejected by the database and logged to the duplicate prevention audit trail.\n"
                "3. **How to check:** Log in to your **Citizen Account** to view your distribution history and exact countdown to your next eligible relief date."
            )
        suggestions = ["⏳ ৫ দিন পর জরুরি আবেদন", "👤 নাগরিক একাউন্ট রেজিস্ট্রেশন", "📞 জরুরি হটলাইন নম্বরসমূহ"]

    # 3. Frequent Question: 5-Day Emergency Protocol
    elif any(k in lower_text or k in text for k in ["৫ দিন", "জরুরি ত্রাণ", "জরুরি আবেদন", "emergency relief", "5-day", "5 day", "আবেদন"]):
        if is_bn:
            reply = (
                "⏳ **৫ দিন পর জরুরি ত্রাণের বিশেষ আবেদন প্রোটোকল:**\n\n"
                "• **জরুরি প্রোটোকল কী?** সাধারণত ত্রাণের স্বাভাবিক বিরতি ৭ দিন। কিন্তু গুরুতর বন্যা পরিস্থিতিতে কোনো পরিবার যদি টানা **৫ দিন কোনো ত্রাণ না পেয়ে থাকে**, তবে তারা বিশেষ জরুরি ত্রাণের আবেদন করতে পারে।\n"
                "• **আবেদনের নিয়ম:**\n"
                "  ১. **Citizen Dashboard** (`citizen_dashboard.php`) এ আপনার NID ও পাসওয়ার্ড দিয়ে লগইন করুন।\n"
                "  ২. 'জরুরি ত্রাণের আবেদন' কার্ডে পরিবারের বর্তমান অবস্থা ও জরুরি প্রয়োজন (খাদ্য/ওষুধ/শিশু খাদ্য) লিখে সাবমিট করুন।\n"
                "  ৩. সংশ্লিষ্ট এলাকার ফিল্ড এনজিও অপারেটরদের কাছে তাৎক্ষণিক অগ্রাধিকার নোটিফিকেশন চলে যাবে।"
            )
        else:
            reply = (
                "⏳ **5-Day Emergency Relief Protocol:**\n\n"
                "• **What is it?** While the standard cooldown is 7 days, families facing severe distress who haven't received relief for **5 consecutive days** are permitted to submit an expedited emergency relief petition.\n"
                "• **How to apply:**\n"
                "  1. Log into your **Citizen Dashboard** (`citizen_dashboard.php`).\n"
                "  2. Complete the emergency application form specifying urgent needs (rations, baby food, clean water).\n"
                "  3. Local registered NGO operators receive immediate priority dispatch alerts."
            )
        suggestions = ["📦 ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম", "👤 নাগরিক একাউন্ট রেজিস্ট্রেশন", "🌊 আবহাওয়া ও বন্যা পরিস্থিতি"]

    # 4. Frequent Question: Weather & River Water Levels
    elif any(k in lower_text or k in text for k in ["আবহাওয়া", "বন্যা পরিস্থিতি", "নদীর পানি", "বিপদসীমা", "বৃষ্টি", "weather", "flood", "river", "danger level", "forecast"]):
        if is_bn:
            reply = (
                "🌊 **লাইভ আবহাওয়া ও নদী বন্যা সতর্কতা:**\n\n"
                "• **মনিটরিং স্টেশন:** ReliefX লাইভ পোর্টালে (`weather_alerts.php`) কুড়িগ্রাম (ধরলা নদী), সুনামগঞ্জ (সুরমা নদী), সিলেট (সুরমা/কুশিয়ারা), জামালপুর (যমুনা নদী) সহ ৮টি প্রধান স্টেশনের ডেটা প্রতি ঘন্টায় আপডেট হয়।\n"
                "• **বর্তমান অবস্থা:** ধরলা ও সুরমা নদীর পানি বিপদসীমার উপর দিয়ে প্রবাহিত হচ্ছে। কুড়িগ্রাম ও সুনামগঞ্জ সদরে উচ্চ সতর্কতা (High Alert) জারি রয়েছে।\n"
                "• বিস্তারিত লাইভ ভিজুয়ালাইজেশন ও গ্রাফ দেখতে উপরের **Weather & Alerts** মেনুতে ক্লিক করুন।"
            )
        else:
            reply = (
                "🌊 **Live Weather & Flood Alert Overview:**\n\n"
                "• **Monitoring Network:** ReliefX tracks 8 critical flood zones (Kurigram Dharla, Sunamganj Surma, Sylhet Kushiyara, Jamalpur Jamuna, etc.) via `weather_alerts.php`.\n"
                "• **Current Warning:** Dharla and Surma rivers are flowing near or above dangerous flood thresholds with continued rainfall forecasted upstream.\n"
                "• Check the top **Weather & Alerts** tab to see real-time river gauges and precipitation trends."
            )
        suggestions = ["🚨 জরুরি উদ্ধার (Emergency Rescue)", "📦 ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম", "📞 জরুরি হটলাইন নম্বরসমূহ"]

    # 5. Frequent Question: Citizen Account Registration & Login
    elif any(k in lower_text or k in text for k in ["নাগরিক একাউন্ট", "রেজিস্ট্রেশন", "খুলব", "একাউন্ট", "citizen", "signup", "register", "login"]):
        if is_bn:
            reply = (
                "👤 **নাগরিক একাউন্ট খোলার সহজ নিয়ম:**\n\n"
                "১. লগইন পেজের নিচে **'Open Citizen Account'** বা `register_user.php` লিঙ্কে ক্লিক করুন।\n"
                "২. আপনার নাম, সক্রিয় মোবাইল নম্বর, ১০ বা ১৭ ডিজিটের **জাতীয় পরিচয়পত্র (NID)** এবং একটি পাসওয়ার্ড লিখুন।\n"
                "৩. একাউন্ট খোলা সম্পন্ন হলে লগইন করে আপনি আপনার পরিবারের সকল পূর্ববর্তী ত্রাণের রেকর্ড, পরবর্তী পাওয়ার তারিখ এবং ৫ দিন পর জরুরি ত্রাণের আবেদন করতে পারবেন।\n"
                "৪. একাউন্ট না খুলেও সাধারণ তথ্য দেখতে চাইলে লগইন পেজের **'Continue as Guest'** বেছে নিতে পারেন।"
            )
        else:
            reply = (
                "👤 **How to Register a Citizen Account:**\n\n"
                "1. Click **'Open Citizen Account'** or visit `register_user.php`.\n"
                "2. Provide your Full Name, Active Phone Number, 10 or 17 digit **NID**, and set a password.\n"
                "3. Once registered, log in to check your family's relief distribution history, upcoming eligibility, and submit 5-day emergency petitions.\n"
                "4. You can also explore public distribution records anytime by clicking **'Continue as Guest'**."
            )
        suggestions = ["📦 ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম", "⏳ ৫ দিন পর জরুরি আবেদন", "🌊 আবহাওয়া ও বন্যা পরিস্থিতি"]

    # 6. Frequent Question: Emergency Hotlines
    elif any(k in lower_text or k in text for k in ["হটলাইন", "নম্বর", "নাম্বার", "ফোন", "কল", "hotline", "number", "numbers", "phone", "contact", "999", "1090"]):
        if is_bn:
            reply = (
                "📞 **জাতীয় জরুরি দুর্যোগ হেল্পলাইন নম্বরসমূহ:**\n\n"
                "• 🚨 **৯৯৯ (999)**: জাতীয় জরুরি সেবা (পুলিশ, অ্যাম্বুলেন্স, ফায়ার সার্ভিস উদ্ধার)\n"
                "• 🌊 **১০৯০ (1090)**: দুর্যোগ প্রস্তুতি ও নদী বন্যা আগাম সতর্কতা বার্তা (টোল-ফ্রি)\n"
                "• ℹ️ **৩৩৩ (333)**: সরকারি তথ্য, সেবা ও সামাজিক সহায়তা হেল্পলাইন\n"
                "• 🩺 **১৬১৬৩ (16163)**: বাংলাদেশ রেড ক্রিসেন্ট সোসাইটি জরুরি সাড়া সেল"
            )
        else:
            reply = (
                "📞 **National Disaster Emergency Hotlines:**\n\n"
                "• 🚨 **999**: National Emergency Services (Police, Ambulance, Fire & Rescue)\n"
                "• 🌊 **1090**: Flood Early Warning & Disaster Weather Helpline (Toll-Free)\n"
                "• ℹ️ **333**: National Citizen Grievance & Government Services Helpline\n"
                "• 🩺 **16163**: Bangladesh Red Crescent Emergency Relief Cell"
            )
        suggestions = ["🚨 জরুরি উদ্ধার (Emergency Rescue)", "📦 ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম", "🌊 আবহাওয়া ও বন্যা পরিস্থিতি"]

    # 7. Greetings & Friendly Default
    elif any(k in lower_text or k in text for k in ["hi", "hello", "hey", "সালাম", "নমস্কার", "কেমন আছেন", "kemon asen"]):
        if is_bn:
            reply = (
                "👋 **নমস্কার / সালাম!** আমি **ReliefX AI স্মার্ট সহকারী**।\n\n"
                "বন্যা ও ত্রাণ সংক্রান্ত যেকোনো তথ্যের জন্য আমি সর্বদা প্রস্তুত। আপনি জানতে পারেন:\n"
                "• 📦 ত্রাণ পাওয়ার যোগ্যতা ও ৭ দিনের নিয়ম\n"
                "• ⏳ ৫ দিন পর জরুরি ত্রাণের আবেদন প্রক্রিয়া\n"
                "• 🌊 লাইভ আবহাওয়া ও নদী বন্যা সতর্কতা\n"
                "• 👤 নাগরিক একাউন্ট তৈরি ও ট্র্যাকিং\n"
                "• 🚨 বিপদে পড়লে তাত্ক্ষণিক উদ্ধার টিকেট"
            )
        else:
            reply = (
                "👋 **Hello!** Welcome to **ReliefX AI Helpline Assistant**.\n\n"
                "How can I assist you today? You can ask me about:\n"
                "• 📦 Relief eligibility and duplicate prevention rules\n"
                "• ⏳ 5-day emergency relief application protocol\n"
                "• 🌊 Real-time river stages and flood forecasts\n"
                "• 👤 Citizen account registration\n"
                "• 🚨 Emergency flood rescue triage"
            )
        suggestions = ["📦 ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম", "🌊 আবহাওয়া ও বন্যা পরিস্থিতি", "📞 জরুরি হটলাইন নম্বরসমূহ"]

    else:
        # Fallback informative query
        if is_bn:
            reply = (
                "ℹ️ আপনার বার্তাটির জন্য ধন্যবাদ। আমি **ReliefX AI হেল্পলাইন**।\n\n"
                "• ত্রাণ বিতরণের যোগ্যতা যাচাই করতে আপনার **NID** নম্বর লিখুন।\n"
                "• জরুরি বন্যায় আটকা পড়ে থাকলে আপনার **অবস্থান বা ল্যান্ডমার্ক** জানান—আমি উদ্ধার টিকেট তৈরি করব।\n"
                "• অথবা নিচের প্রস্তাবিত প্রশ্নগুলোর যেকোনো একটিতে ক্লিক করুন।"
            )
        else:
            reply = (
                "ℹ️ Thank you for your inquiry. I am the **ReliefX AI Helpline**.\n\n"
                "• Enter your **NID** to check family relief status.\n"
                "• If stranded by floodwaters, share your **landmark or GPS** for an immediate rescue ticket.\n"
                "• Or select one of the quick topics below."
            )
        suggestions = ["📦 ত্রাণ পাওয়ার যোগ্যতা ও নিয়ম", "⏳ ৫ দিন পর জরুরি আবেদন", "📞 জরুরি হটলাইন নম্বরসমূহ"]

    return ChatWebhookResponse(
        reply=reply,
        is_emergency=is_emergency,
        ticket=ticket,
        suggested_actions=suggestions
    )


# ----------------------------------------------------------------------------
# 4. SOS PORTAL & SPATIAL CLUSTERING ENDPOINT (DBSCAN Simulation)
# ----------------------------------------------------------------------------
@app.post("/api/v1/sos-request", response_model=SosSubmissionResponse)
def submit_sos_request(req: SosSubmissionRequest):
    """
    Accepts public citizen SOS distress signals. Simulates Spatial Clustering
    via DBSCAN (eps=50m, min_samples=2) to cluster nearby trapped individuals into
    an actionable high-density 'Rescue Node' for emergency response teams.
    """
    sos_id = f"SOS-BD-{random.randint(10000, 99999)}"
    
    # Calculate mock spatial cluster centroid (within 30-50m of submitted GPS)
    lat_jitter = random.uniform(-0.0003, 0.0003)
    lon_jitter = random.uniform(-0.0003, 0.0003)
    centroid_lat = round(req.lat + lat_jitter, 6)
    centroid_lon = round(req.lon + lon_jitter, 6)

    # Simulated existing cluster grouping
    cluster_num = random.randint(3, 9)
    cluster_id = f"RESCUE-NODE-KM-0{cluster_num}"
    active_requests_in_cluster = random.randint(3, 7)
    total_people_in_cluster = req.people_count + random.randint(12, 28)

    cluster_info = RescueNodeCluster(
        cluster_id=cluster_id,
        cluster_name=f"Rescue Node #{cluster_num} — {req.landmark[:35]} Sector",
        total_people_in_node=total_people_in_cluster,
        active_sos_requests=active_requests_in_cluster,
        centroid_lat=centroid_lat,
        centroid_lon=centroid_lon,
        cluster_radius_meters=42.5,
        clustering_algorithm="DBSCAN (eps=50m, metric=haversine)",
        dispatch_priority="HIGH-PRIORITY (Cluster Level 1)",
        assigned_rescue_team="বাংলাদেশ কোস্টগার্ড ও নৌবাহিনী স্পিডবোট দল (Coast Guard / Navy Unit)",
        estimated_reach_time="20-30 minutes",
        status="DISPATCH_IN_PROGRESS"
    )

    return SosSubmissionResponse(
        status="success",
        message="SOS সংকেত সফলভাবে গৃহীত এবং ক্লাস্টার নোডে অন্তর্ভুক্ত হয়েছে। উদ্ধারকারী দল পাঠানো হচ্ছে।" 
                if any('\u0980' <= c <= '\u09FF' for c in req.landmark + req.name)
                else "SOS Signal registered and grouped into Spatial Rescue Node successfully. Rescue unit alerted.",
        sos_id=sos_id,
        timestamp=datetime.utcnow().isoformat() + "Z",
        submitted_data={
            "name": req.name,
            "phone": req.phone,
            "people_count": req.people_count,
            "urgency": req.urgency_level,
            "needs": req.needs,
            "landmark": req.landmark,
            "submitted_gps": f"{req.lat}, {req.lon}"
        },
        rescue_node=cluster_info
    )


# ============================================================================
# STANDALONE RUNNER
# ============================================================================
if __name__ == "__main__":
    import uvicorn
    # Allow port override from env, default to 8000 (or 8001 if specified)
    port = int(os.environ.get("FASTAPI_PORT", 8000))
    print(f"🚀 Starting ReliefX AI Microservice on http://0.0.0.0:{port}")
    uvicorn.run("main:app", host="0.0.0.0", port=port, reload=True)

