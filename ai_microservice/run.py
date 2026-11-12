"""
ReliefX AI Microservice Runner
Checks port availability and launches Uvicorn server for FastAPI.
"""
import os
import sys
import socket
import uvicorn

def is_port_in_use(port: int, host: str = "127.0.0.1") -> bool:
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.settimeout(0.5)
        return s.connect_ex((host, port)) == 0

def find_available_port(preferred_ports=(8000, 8001, 8081, 5000)) -> int:
    for p in preferred_ports:
        if not is_port_in_use(p):
            return p
    return 8001

if __name__ == "__main__":
    target_port = int(os.environ.get("FASTAPI_PORT", 8000))
    if is_port_in_use(target_port):
        print(f"⚠️ Port {target_port} is currently in use (possibly by Apache or PHP server).")
        target_port = find_available_port((8000, 8001, 8081, 5000))
        print(f"✅ Binding FastAPI microservice to alternate port: {target_port}")
    else:
        print(f"✅ Port {target_port} is available for FastAPI microservice.")

    print(f"🚀 Starting ReliefX AI Microservice on http://127.0.0.1:{target_port}")
    print(f"📚 Interactive Swagger API Docs at: http://127.0.0.1:{target_port}/docs")

    uvicorn.run("main:app", host="0.0.0.0", port=target_port, reload=True, app_dir=os.path.dirname(__file__))

