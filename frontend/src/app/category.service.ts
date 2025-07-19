import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { AuthService } from './auth.service';

export interface Categoria {
  id_categoria: number;
  nombre_categoria: string;
  cantidad_posts: number;
  cantidad_comentarios: number;
}

@Injectable({
  providedIn: 'root'
})
export class CategoryService {

  private apiUrl = 'http://localhost:8012/miproyecto/api/categorias';

  constructor(
    private http: HttpClient,
    private authService: AuthService
  ) {}

  getCategorias(): Observable<{ categorias: Categoria[] }> {
    return this.http.get<{ categorias: Categoria[] }>(this.apiUrl);
  }

  crearCategoria(categoria: { nombre_categoria: string }) {
    const token = this.authService.getToken();

    const headers = new HttpHeaders({
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json'
    });

    return this.http.post(this.apiUrl, categoria, { headers });
  }

  eliminarCategoria(id_categoria: number) {
  const token = this.authService.getToken();

  const headers = new HttpHeaders({
    Authorization: `Bearer ${token}`
  });

  const url = `${this.apiUrl}/${id_categoria}`;
  return this.http.delete(url, { headers });
}


}