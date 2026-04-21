"""
SemreMETA - Instagram Private API Microservice
instagrapi kullanarak resmi Meta API olmadan Instagram DM işlemleri yapar.
Çalıştırmak: uvicorn main:app --host 127.0.0.1 --port 8765
"""

from fastapi import FastAPI, HTTPException, Header, Depends
from pydantic import BaseModel
from instagrapi import Client
from instagrapi.exceptions import (
    LoginRequired, TwoFactorRequired, ChallengeRequired,
    BadPassword, InvalidMediaId, UserNotFound
)
from typing import Optional
import json, os, logging
from datetime import datetime

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(title="SemreMETA Instagram Service")

SESSIONS_DIR = os.path.join(os.path.dirname(__file__), "sessions")
API_KEY = os.environ.get("IG_SERVICE_API_KEY", "semremeta_ig_2024")
os.makedirs(SESSIONS_DIR, exist_ok=True)

# Bellekte aktif client'ları tut (restart'ta session dosyasından yüklenir)
clients: dict[str, Client] = {}


def verify_api_key(x_api_key: str = Header(...)):
    if x_api_key != API_KEY:
        raise HTTPException(status_code=403, detail="Geçersiz API key")
    return x_api_key


def get_session_file(username: str) -> str:
    safe = username.replace("/", "_").replace(".", "_")
    return os.path.join(SESSIONS_DIR, f"{safe}.json")


def load_client(username: str) -> Client:
    """Session dosyasından client yükle."""
    if username in clients:
        return clients[username]

    session_file = get_session_file(username)
    if not os.path.exists(session_file):
        raise HTTPException(status_code=401, detail=f"'{username}' için oturum bulunamadı. Yeniden giriş yapın.")

    cl = Client()
    try:
        cl.load_settings(session_file)
        cl.login(username, "")  # Session ile giriş
        clients[username] = cl
        logger.info(f"Session yüklendi: {username}")
        return cl
    except Exception as e:
        logger.error(f"Session yüklenemedi ({username}): {e}")
        # Session geçersiz, dosyayı sil
        os.remove(session_file)
        raise HTTPException(status_code=401, detail=f"Oturum süresi dolmuş. '{username}' için yeniden giriş yapın.")


# ─── Request Models ───────────────────────────────────────────────────────────

class LoginRequest(BaseModel):
    username: str
    password: str
    verification_code: Optional[str] = None  # 2FA için


class SendMessageRequest(BaseModel):
    ig_username: str  # hangi hesaptan gönderileceği
    thread_id: str
    text: str


class SendToUserRequest(BaseModel):
    ig_username: str
    recipient_username: str
    text: str


# ─── Endpoints ───────────────────────────────────────────────────────────────

@app.get("/health")
def health():
    return {"status": "ok", "sessions": list(clients.keys())}


@app.post("/login")
def login(req: LoginRequest, _=Depends(verify_api_key)):
    """Instagram'a kullanıcı adı ve şifre ile giriş yap."""
    cl = Client()
    session_file = get_session_file(req.username)

    try:
        # Varolan session'ı dene
        if os.path.exists(session_file):
            cl.load_settings(session_file)

        cl.login(req.username, req.password)
        cl.dump_settings(session_file)
        clients[req.username] = cl

        try:
            user = cl.user_info_by_username(req.username)
            profile_pic = str(user.profile_pic_url) if user.profile_pic_url else None
            full_name = user.full_name or req.username
        except Exception:
            profile_pic = None
            full_name = req.username

        logger.info(f"Giriş başarılı: {req.username} (user_id: {cl.user_id})")
        return {
            "success": True,
            "user_id": str(cl.user_id),
            "username": cl.username or req.username,
            "full_name": full_name,
            "profile_pic_url": profile_pic,
        }

    except TwoFactorRequired:
        raise HTTPException(
            status_code=400,
            detail="2FA_REQUIRED: Bu hesapta iki faktörlü doğrulama aktif. Instagram ayarlarından geçici olarak devre dışı bırakın veya uygulama şifresi oluşturun."
        )
    except ChallengeRequired:
        raise HTTPException(
            status_code=400,
            detail="CHALLENGE_REQUIRED: Instagram şüpheli giriş tespit etti. Instagram mobil uygulamasından hesabınızı doğrulayın, sonra tekrar deneyin."
        )
    except BadPassword:
        raise HTTPException(status_code=400, detail="Kullanıcı adı veya şifre hatalı.")
    except Exception as e:
        logger.error(f"Login hatası ({req.username}): {e}")
        raise HTTPException(status_code=400, detail=f"Giriş yapılamadı: {str(e)}")


@app.delete("/logout/{ig_username}")
def logout(ig_username: str, _=Depends(verify_api_key)):
    """Hesap oturumunu kapat."""
    session_file = get_session_file(ig_username)
    if os.path.exists(session_file):
        os.remove(session_file)
    if ig_username in clients:
        del clients[ig_username]
    return {"success": True}


@app.get("/threads/{ig_username}")
def get_threads(ig_username: str, amount: int = 20, _=Depends(verify_api_key)):
    """DM konuşmalarını listele."""
    cl = load_client(ig_username)

    try:
        threads = cl.direct_threads(amount=amount)
        result = []

        for t in threads:
            other_users = [u for u in t.users if str(u.pk) != str(cl.user_id)]
            if not other_users:
                continue

            last_msg = None
            last_msg_type = "text"
            if t.messages:
                m = t.messages[0]
                last_msg = m.text or ""
                last_msg_type = m.item_type or "text"

            result.append({
                "id": t.id,
                "users": [
                    {
                        "pk": str(u.pk),
                        "username": u.username,
                        "full_name": u.full_name or u.username,
                        "profile_pic_url": str(u.profile_pic_url) if u.profile_pic_url else None,
                    }
                    for u in other_users
                ],
                "last_message": last_msg,
                "last_message_type": last_msg_type,
                "last_activity": t.last_activity_at.isoformat() if t.last_activity_at else None,
                "unread_count": t.unread_count or 0,
            })

        return result

    except LoginRequired:
        if ig_username in clients:
            del clients[ig_username]
        raise HTTPException(status_code=401, detail="Oturum süresi doldu. Yeniden giriş yapın.")
    except Exception as e:
        logger.error(f"get_threads hatası ({ig_username}): {e}")
        raise HTTPException(status_code=500, detail=str(e))


@app.get("/threads/{ig_username}/{thread_id}/messages")
def get_messages(ig_username: str, thread_id: str, amount: int = 30, _=Depends(verify_api_key)):
    """Bir konuşmadaki mesajları getir."""
    cl = load_client(ig_username)

    try:
        messages = cl.direct_messages(thread_id, amount=amount)
        result = []

        for m in reversed(messages):
            result.append({
                "id": m.id,
                "text": m.text or "",
                "user_id": str(m.user_id),
                "timestamp": m.timestamp.isoformat() if m.timestamp else None,
                "item_type": m.item_type or "text",
                "is_outgoing": str(m.user_id) == str(cl.user_id),
                "media_url": str(m.clip.video_url) if hasattr(m, "clip") and m.clip else None,
            })

        return result

    except LoginRequired:
        if ig_username in clients:
            del clients[ig_username]
        raise HTTPException(status_code=401, detail="Oturum süresi doldu. Yeniden giriş yapın.")
    except Exception as e:
        logger.error(f"get_messages hatası ({ig_username}/{thread_id}): {e}")
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/send")
def send_message(req: SendMessageRequest, _=Depends(verify_api_key)):
    """Thread'e mesaj gönder."""
    cl = load_client(req.ig_username)

    try:
        result = cl.direct_send(req.text, thread_ids=[req.thread_id])
        return {
            "success": True,
            "message_id": result.id if result else None,
        }
    except LoginRequired:
        if req.ig_username in clients:
            del clients[req.ig_username]
        raise HTTPException(status_code=401, detail="Oturum süresi doldu. Yeniden giriş yapın.")
    except Exception as e:
        logger.error(f"send_message hatası: {e}")
        raise HTTPException(status_code=500, detail=f"Mesaj gönderilemedi: {str(e)}")


@app.post("/send-to-user")
def send_to_user(req: SendToUserRequest, _=Depends(verify_api_key)):
    """Kullanıcı adına göre yeni konuşma başlat ve mesaj gönder."""
    cl = load_client(req.ig_username)

    try:
        user = cl.user_info_by_username(req.recipient_username)
        result = cl.direct_send(req.text, user_ids=[user.pk])
        return {
            "success": True,
            "message_id": result.id if result else None,
            "thread_id": result.thread_id if result and hasattr(result, "thread_id") else None,
        }
    except UserNotFound:
        raise HTTPException(status_code=404, detail=f"Kullanıcı bulunamadı: {req.recipient_username}")
    except Exception as e:
        logger.error(f"send_to_user hatası: {e}")
        raise HTTPException(status_code=500, detail=str(e))
