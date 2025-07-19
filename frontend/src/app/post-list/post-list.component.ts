import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { CommonModule } from '@angular/common';
import { PostService } from '../post.service';
import { LikeService } from '../likes.service';
import { CommentService } from '../comment.service';
import { RouterModule } from '@angular/router';
import { take } from 'rxjs';
import { CategoryService } from '../category.service';
import { AuthService } from '../auth.service';
import { HttpClient, HttpHeaders } from '@angular/common/http'; 
import { UserService } from '../user.service';// 👈 Agregado
import Swal from 'sweetalert2';

@Component({
  selector: 'app-post-list',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './post-list.component.html',
  styleUrls: ['./post-list.component.css']
})
export class PostListComponent implements OnInit {
  idCategoria!: number;
  posts: any[] = [];
  isLoading = true;
  nombreCategoria: string = '';
  userId: number = 0;
  userRole: string = '';

  constructor(
    private route: ActivatedRoute,
    private postService: PostService,
    private likeService: LikeService,
    private commentService: CommentService,
    private categoryService: CategoryService,
    private authService: AuthService,
    private http: HttpClient,
    private userService: UserService, // 👈 Agregado
  ) {}

  ngOnInit(): void {
    this.userService.getUsuario().subscribe(user => {
      if (user) {
        this.userId = user.id;
        this.userRole = user.rol;
      }
    });

    this.route.paramMap.pipe(take(1)).subscribe(params => {
      const id = params.get('id');
      if (id) {
        this.idCategoria = +id;

        this.categoryService.getCategorias().subscribe({
          next: (resp) => {
            const categoria = resp.categorias.find((c: any) => c.id_categoria == this.idCategoria);
            this.nombreCategoria = categoria ? categoria.nombre_categoria : '';
          },
          error: (err) => {
            console.error('Error al obtener categorías', err);
            this.nombreCategoria = '';
          }
        });

        this.cargarPosts();
      }
    });
  }

cargarPosts(): void {
  this.isLoading = true;
  this.posts = [];

  this.postService.getPostsConParametros(this.idCategoria).subscribe({
    next: (response) => {
      const posts = response.posts ?? response;

      posts.forEach((post: any) => {
         
        post.likes = 0;
        post.vistas = post.cantidad_vistas ?? 0;
        post.comentarios = 0;

       
        this.likeService.getLikes(post.id_post).subscribe({
          next: (res) => {
            post.likes = res.total_likes;
          },
          error: (err) => {
            console.warn(`Error al obtener likes del post ${post.id_post}`, err);
          }
        });

        this.commentService.getComentarios(post.id_post).subscribe({
          next: (res) => {
            post.comentarios = res.comentarios.length;
          },
          error: (err) => {
            console.warn(`Error al obtener comentarios del post ${post.id_post}`, err);
          }
        });
      });

      this.posts = posts;
      this.isLoading = false;
    },
    error: (error) => {
      console.error('Error al obtener los posts:', error);
      this.isLoading = false;
    }
  });
}


eliminarPost(id_post: number) {
  Swal.fire({
    title: '¿Estás seguro?',
    text: 'Esta acción eliminará el post de forma permanente.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      const headers = new HttpHeaders({
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + this.authService.getToken()
      });

      const body = { id_post: id_post };

      this.http.post('http://localhost:8012/miproyecto/api/index.php?comando=deletePost', body, { headers })
        .subscribe({
          next: (respuesta: any) => {
            console.log('Post eliminado:', respuesta);
            Swal.fire('Eliminado', 'El post ha sido eliminado correctamente.', 'success');
            this.cargarPosts(); // Recargar la lista
          },
          error: (error) => {
            console.error('Error al eliminar el post: ', error);
            Swal.fire('Error', 'No se pudo eliminar el post. Intenta nuevamente.', 'error');
          }
        });
    }
  });
}
}
