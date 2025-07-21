import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, timer, BehaviorSubject } from 'rxjs';
import { switchMap } from 'rxjs/operators';
import { AuthService } from './auth.service';

export interface Notificacion {
  id_notificacion: number;
  mensaje: string;
  leido: number;
  fecha_envio: string;
}

@Injectable({
  providedIn: 'root'
})
export class NotificationService {
  private apiUrl = 'http://localhost:8012/miproyecto/api';
  private notificacionesSubject = new BehaviorSubject<Notificacion[]>([]);
  notificaciones$ = this.notificacionesSubject.asObservable();

  constructor(private http: HttpClient, private authService: AuthService) {
    // Opcional: refrescar notificaciones periódicamente cada 30 segundos
    timer(0, 30000).pipe(
      switchMap(() => this.obtenerNotificaciones())
    ).subscribe(notifs => this.notificacionesSubject.next(notifs));
  }

  private getHeaders(): HttpHeaders {
    const token = this.authService.getToken() || '';
    return new HttpHeaders({
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json'
    });
  }

  obtenerNotificaciones(): Observable<Notificacion[]> {
    return this.http.get<{ notificaciones: Notificacion[] }>(
      `${this.apiUrl}/index.php?comando=getNotificaciones`,  // <-- aquí cambia
      { headers: this.getHeaders() }
    ).pipe(
      switchMap(response => {
        return new Observable<Notificacion[]>(observer => {
          observer.next(response.notificaciones);
          observer.complete();
        });
      })
    );
  }

  marcarLeida(id_notificacion: number): Observable<any> {
    return this.http.patch(
      `${this.apiUrl}/index.php?comando=Notificaciones`, // <-- aquí cambia
      { id_notificacion },
      { headers: this.getHeaders() }
    );
  }
}
