import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface PostData {
  id_categoria: number;
  titulo: string;
  contenido: string;
}

@Injectable({
  providedIn: 'root'
})
export class PostService {
  private apiUrl = 'http://localhost:8012/miproyecto/api';

  constructor(private http: HttpClient) { }

   private getHeaders() {
    const token = localStorage.getItem('jwt_token') || '';
    return new HttpHeaders({
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    });
  }

  crearPost(postData: PostData): Observable<any> {
    const token = localStorage.getItem('jwt_token');

    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    });

    return this.http.post(`${this.apiUrl}/posts`, postData, { headers });
  }

  getPostsConParametros(id_categoria: number): Observable<any> {
    const token = localStorage.getItem('jwt_token');

    const headers = new HttpHeaders({
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    });

    return this.http.get(`${this.apiUrl}/posts/${id_categoria}`, { headers });
  }

  getPostDetalle(id_post: number): Observable<{ post: any }> {
    return this.http.get<{ post: any }>(`${this.apiUrl}/postdetalle/${id_post}`);
  }
  
registrarVisita(id_post: number): Observable<any> {
  const token = localStorage.getItem('jwt_token') || '';
  const headers = new HttpHeaders({
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  });
  return this.http.post(`${this.apiUrl}/postview.php?comando=registrarVisita`, { id_post }, { headers });
}

eliminarPost(id_post: number): Observable<any> {
  const token = localStorage.getItem('jwt_token') || '';
  const headers = new HttpHeaders({
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  });

  const url = `${this.apiUrl}/index.php?comando=deletePost`;
  return this.http.post(url, { id_post }, { headers });
}

buscarPosts(query: string) {
  return this.http.get<any>(`http://localhost:8012/miproyecto/api/index.php?comando=buscarPosts&query=${encodeURIComponent(query)}`);
}



}
