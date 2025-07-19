import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { PostService } from '../post.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { CommentService } from '../comment.service';
import { formatDistanceToNow } from 'date-fns';
import { UserService } from '../user.service';
import { LikeService } from '../likes.service';
import { es } from 'date-fns/locale';


@Component({
  selector: 'app-post-detail',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './post-detail.component.html',
  styleUrls: ['./post-detail.component.css']
})
export class PostDetailComponent implements OnInit {

  post: any;
  comments: any[] = [];
  newComment: string = '';
  replyingTo: number | null = null;
  parentCommentAuthor: string | null = null;
  userId: number = 0;
  userRole: string = '';
  likes: { total_likes: number; user_liked: boolean } | null = null;


  constructor(
    private route: ActivatedRoute,
    private postService: PostService,
    private commentService: CommentService,
    private userService: UserService,
    private likeService: LikeService
  ) { }

  ngOnInit(): void {

    // Cargar usuario logueado

    this.userService.getUsuario().subscribe(user => {
      if (user) {
        this.userId = user.id;
        this.userRole = user.rol;
      }
    });
    // Cargar post y comentarios

    this.route.paramMap.subscribe(params => {
      const idPost = params.get('id');
      if (idPost) {
        this.cargarPost(+idPost);
        this.cargarComentarios(+idPost);
      }
    });

  }

  cargarLikes(): void {
    if (!this.post) return;

    this.likeService.getLikes(this.post.id_post).subscribe({
      next: (res) => this.likes = res,
      error: (err) => console.error('Error cargando likes', err)
    });
  }

toggleLike(): void {
  if (!this.post || !this.likes) return;
  

  const accion = this.likes.user_liked
    ? this.likeService.quitarLike(this.post.id_post)
    : this.likeService.darLike(this.post.id_post);

  accion.subscribe({
    next: () => this.cargarLikes(),
    error: (err) => console.error('Error al cambiar like', err)
  });

  
}

  cargarPost(id: number): void {
    this.postService.getPostDetalle(id).subscribe({
      next: (resp) => {
        this.post = resp.post;


        this.postService.registrarVisita(id).subscribe({
          next: () => {
            console.log('Visita registrada');
            // Recargar post para actualizar la cantidad de vistas
            this.postService.getPostDetalle(id).subscribe({
              next: (resp) => {
                this.post = resp.post;
                this.cargarLikes(); // Cargar likes después de recargar el post
              },
              error: (err) => console.error('Error recargando post después de registrar visita', err)
            });
          },
          error: (err) => console.error('Error registrando visita', err)
        });
      },
      error: (err) => {
        console.error('Error al cargar el post:', err);
      }
    });
  }



  cargarComentarios(postId: number): void {
    this.commentService.getComentarios(postId).subscribe({
      next: (resp) => {
        this.comments = resp.comentarios;
      },
      error: (err) => console.error('Error cargando comentarios', err)
    });
  }


  getInitials(nombre: string): string {
    const partes = nombre.split(' ');
    return partes.map(p => p.charAt(0)).join('').toUpperCase();
  }

  submitComment(): void {
    if (!this.newComment.trim()) return;

    const comentario = {
      id_post: this.post.id_post,
      contenido: this.newComment.trim(),
      id_comentario_padre: this.replyingTo // Agregamos esto
    };

    this.commentService.crearComentario(comentario).subscribe({
      next: () => {
        this.newComment = '';
        this.replyingTo = null; // Resetear después de enviar
        this.cargarComentarios(this.post.id_post);
      },
      error: (err) => {
        console.error('Error al publicar comentario', err);
      }
    });
  }

  replyToComment(commentId: number, authorName: string): void {
    this.replyingTo = commentId;
    this.parentCommentAuthor = authorName;
  }

  cancelReply(): void {
    this.replyingTo = null;
  }


  // Formatear fecha

  formatFechaRelativa(fecha: string): string {
    if (!fecha) return '';
    const fechaDate = new Date(fecha);
    const diferencia = formatDistanceToNow(fechaDate, { addSuffix: true, locale: es });

    // Opcional: reemplazar "hace menos de un minuto" por "Hace un momento"
    if (diferencia === 'menos de un minuto') {
      return 'Hace un momento';
    }
    return diferencia.charAt(0).toUpperCase() + diferencia.slice(1); // Capitaliza la primera letra
  }

  eliminarComentario(id_comentario: number): void {
    if (!confirm('¿Estás seguro que deseas eliminar este comentario?')) return;

    this.commentService.eliminarComentario(id_comentario).subscribe({
      next: () => {
        this.cargarComentarios(this.post.id_post);
      },
      error: (err) => {
        console.error('Error al eliminar comentario', err);
        alert('No se pudo eliminar el comentario.');
      }
    });
  }
}

