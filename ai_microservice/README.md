# ReliefX AI Microservice (v3.0)

A modern, high-performance **FastAPI** predictive microservice for the ReliefX Disaster Management Platform.

## 🚀 Features

1. **Flood Prediction API (`GET /api/v1/flood-prediction`)**:
   - Evaluates real-time flood risk at any given GPS coordinates.
   - Returns risk level (`Low`, `Moderate`, `Severe`, `Critical`), water level vs. danger mark delta, rainfall forecast, and hydrological actions.

2. **Geo-Urgency & Warehouse Dispatch Prioritization (`GET /api/v1/geo-urgency`)**:
   - Computes composite urgency index:
     $$\text{Urgency Index} = 0.45 \times \text{Flood Risk} + 0.35 \times \text{SOS Density} + 0.20 \times \text{Underserved Factor}$$
   - Generates ranked dispatch queue for regional NGO warehouses with stock suggestions and estimated transit times.

3. **Citizen AI Helpline Chatbot (`POST /api/v1/chat/webhook`)**:
   - Bilingual (English & Bengali) conversational agent.
   - Extracts crisis entities (**Name**, **GPS Coordinates**, and **Landmark**).
   - Generates automated **Emergency Rescue Tickets** with tracking IDs and units.

4. **SOS Distress Portal & Spatial Clustering (`POST /api/v1/sos-request`)**:
   - Accepts public citizen SOS submissions.
   - Simulates **DBSCAN Spatial Clustering** (50m radius) to group nearby stranded citizens into high-density **Rescue Nodes** for tactical armed forces / coast guard deployment.

---

## 🛠️ Quick Start

### 1. Install Dependencies
```bash
pip install -r requirements.txt
```

### 2. Start the Microservice
```bash
# Run with automatic port detection (default 8000, fallback 8001 if PHP is on 8000)
python run.py

# Or directly with Uvicorn on specific port:
uvicorn main:app --host 0.0.0.0 --port 8001 --reload
```

### 3. Interactive Documentation
- **Swagger UI:** `http://localhost:8001/docs` (or `:8000/docs`)
- **ReDoc:** `http://localhost:8001/redoc`
