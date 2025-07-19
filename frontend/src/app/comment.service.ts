import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface Comentario {
  id_comentario: number;
  contenido: string;
  fecha_comentario: string;
  nombre_completo: string;
  avatar: string;
  id_usuario: number; 
  id_comentario_padre: number | null;
  respuestas?: Comentario[];
}
@Injectable({
  providedIn: 'root'
})
export class CommentService {
  private apiUrl = 'http://localhost:8012/miproyecto/api';

  constructor(private http: HttpClient) { }

  getComentarios(postId: number): Observable<{ comentarios: Comentario[] }> {
    return this.http.get<{ comentarios: Comentario[] }>(`${this.apiUrl}/comentarios/${postId}`);
  }

  crearComentario(data: { id_post: number; contenido: string }): Observable<any> {
    const token = localStorage.getItem('jwt_token');
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    });

    return this.http.post(`${this.apiUrl}/comentarios`, data, { headers });
  }

  eliminarComentario(id_comentario: number): Observable<any> {
    const token = localStorage.getItem('jwt_token');
    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`
    });

    return this.http.delete(`${this.apiUrl}/comentarios?id_comentario=${id_comentario}`, { headers });
  }


}
