import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { AuthService } from './auth.service';

@Injectable({
  providedIn: 'root'
})
export class LikeService {
  private apiUrl = 'http://localhost:8012/miproyecto/api';

  constructor(private http: HttpClient, private authService: AuthService) {}

  private getHeaders(): HttpHeaders {
    const token = this.authService.getToken() || '';
    return new HttpHeaders({
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    });
  }

  getLikes(postId: number): Observable<{ total_likes: number; user_liked: boolean }> {
    return this.http.get<{ total_likes: number; user_liked: boolean }>(
      `${this.apiUrl}/likespost/${postId}`,
      { headers: this.getHeaders() }
    );
  }

  darLike(postId: number): Observable<any> {
    return this.http.post(
      `${this.apiUrl}/like_post`,
      { id_post: postId },
      { headers: this.getHeaders() }
    );
  }

  quitarLike(postId: number): Observable<any> {
    return this.http.delete(
      `${this.apiUrl}/likepost?id_post=${postId}`,
      { headers: this.getHeaders() }
    );
  }
}
