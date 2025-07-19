import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { tap } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private apiUrl = 'http://localhost:8012/miproyecto/api';

  constructor(private http: HttpClient) {}

  login(credenciales: { email: string; clave: string }) {
    return this.http.post(this.apiUrl + '/index.php?comando=login', credenciales).pipe(
      tap((response: any) => {
        if (response.jwt) {
          localStorage.setItem('jwt_token', response.jwt);
        }
      })
    );
  }

  logout(): void {
    localStorage.removeItem('jwt_token');
  }

  getToken(): string | null {
    return localStorage.getItem('jwt_token');
  }


  // PREGUNTAR A SANTI
  private decodePayload(token: string): any | null {
    try {
      const payloadBase64Url = token.split('.')[1];
      const payloadBase64 = payloadBase64Url.replace(/-/g, '+').replace(/_/g, '/');
      const payloadJson = atob(payloadBase64);
      return JSON.parse(payloadJson);
    } catch {
      return null;
    }
  }

  esVigente(): boolean {
    const token = this.getToken();
    if (token) {
      try {
        // Usamos decodePayload para decodificar
        const payload = this.decodePayload(token);
        const expira = payload?.exp || 0;
        const horaActual = Math.floor(Date.now() / 1000);
        return expira > horaActual;
      } catch {
        return false;
      }
    }
    return false;
  }

  estaAutenticado(): boolean {
    return this.esVigente();
  }

  getUsuarioLogueado() {
    const token = this.getToken();
    if (!token) return null;

    return this.http.get<any>(this.apiUrl + '/index.php?comando=usuariologueado', {
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
  }

  getRol(): string | null {
    const token = this.getToken();
    if (!token) return null;

    const payload = this.decodePayload(token);
    return payload?.rol || null;
  }
}
