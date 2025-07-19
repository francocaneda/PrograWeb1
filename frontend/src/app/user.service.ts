import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { BehaviorSubject, Observable, of } from 'rxjs';
import { catchError, tap } from 'rxjs/operators';
import { AuthService } from './auth.service';

interface Usuario {
  id: number;
  user_nameweb: string;
  email: string;
  nombre: string;
  apellido: string;
  avatar: string;
  fecha_nacimiento: string;
  bio: string;
  rol: string;
}

@Injectable({
  providedIn: 'root'
})
export class UserService {
  private apiUrl = 'http://localhost:8012/miproyecto/api';
  private usuarioSubject = new BehaviorSubject<Usuario | null>(null);

  constructor(private http: HttpClient, private authService: AuthService) {
    if (this.authService.estaAutenticado()) {
      this.cargarUsuario();
    }
  }

  getUsuario(): Observable<Usuario | null> {
    return this.usuarioSubject.asObservable();
  }

  cargarUsuario(): void {
    const token = this.authService.getToken();
    if (!token) {
      this.usuarioSubject.next(null);
      return;
    }

    const headers = new HttpHeaders({
      Authorization: `Bearer ${token}`
    });

    this.http.get<{ usuario: Usuario }>(`${this.apiUrl}/index.php?comando=usuariologueado`, { headers })
      .pipe(
        tap(res => this.usuarioSubject.next(res.usuario)),
        catchError(err => {
          console.error('Error al cargar el usuario logueado:', err);
          this.usuarioSubject.next(null);
          return of(null);
        })
      )
      .subscribe();
  }

  limpiarUsuario(): void {
    this.usuarioSubject.next(null);
  }

getUsuariosAntiguos(): Observable<any> {
  const token = this.authService.getToken();
  const headers = new HttpHeaders({
    Authorization: `Bearer ${token}`
  });

  return this.http.get('http://localhost:8012/miproyecto/api/index.php?comando=usuariosAntiguos', { headers });
}


  // Nuevos mÃ©todos para stats

  getTotalUsuarios(): Observable<{ total_usuarios: number }> {
    return this.http.get<{ total_usuarios: number }>(`${this.apiUrl}/index.php?comando=totalUsuarios`);
  }

  getTotalPosts(): Observable<{ total_posts: number }> {
    return this.http.get<{ total_posts: number }>(`${this.apiUrl}/index.php?comando=totalPosts`);
  }

  getTotalComentarios(): Observable<{ total_comentarios: number }> {
    return this.http.get<{ total_comentarios: number }>(`${this.apiUrl}/index.php?comando=totalComentarios`);
  }
}