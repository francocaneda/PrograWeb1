import { Component, OnInit } from '@angular/core';
import { CategoryService, Categoria } from '../category.service';
import { AuthService } from '../auth.service';
import { CommonModule } from '@angular/common';
import { HttpClientModule } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { routes } from '../app.routes';
import { Router } from '@angular/router';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-category',
  standalone: true,
  imports: [CommonModule, HttpClientModule, FormsModule],
  templateUrl: './category.component.html',
  styleUrl: './category.component.css'
})
export class CategoryComponent implements OnInit {

  categorias: Categoria[] = [];
  isLoading: boolean = true;
  isAuthenticated: boolean = false;
  isAdmin: boolean = false;

  nuevoNombreCategoria: string = '';
  mensajeExito: string = '';
  mostrarMensaje: boolean = false;

  constructor(
    private categoryService: CategoryService,
    private authService: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.isAuthenticated = this.authService.estaAutenticado();
    this.isAdmin = this.authService.getRol() === 'admin';

    if (this.isAuthenticated) {
      this.cargarCategorias();
    } else {
      this.isLoading = false;
    }
  }

  cargarCategorias() {
    this.categoryService.getCategorias().subscribe({
      next: (resp) => {
        this.categorias = resp.categorias;
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Error al cargar categorías', err);
        this.isLoading = false;
      }
    });
  }

  crearCategoria() {
    if (!this.nuevoNombreCategoria.trim()) {
      return;
    }

    this.categoryService.crearCategoria({ nombre_categoria: this.nuevoNombreCategoria.trim() }).subscribe({
      next: (resp) => {
        this.nuevoNombreCategoria = '';
        this.cargarCategorias();

        this.mensajeExito = '¡Categoría creada exitosamente!';
        this.mostrarMensaje = true;

        setTimeout(() => {
          this.mostrarMensaje = false;
        }, 3000);
      },
      error: (err) => {
        console.error(err);
      }
    });
  }

  verPosts(categoria: any) {
    this.router.navigate(['/categoria', categoria.id_categoria, categoria.nombre_categoria]);
  }

  cargarPosts(categoria: any): void {
    const id = categoria.id_categoria;
    this.router.navigate(['/main-layout/categorias', id]);
  }

eliminarCategoria(id_categoria: number) {
  Swal.fire({
    title: '¿Estás seguro?',
    text: 'Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  }).then((result) => {
    if (result.isConfirmed) {
      this.categoryService.eliminarCategoria(id_categoria).subscribe({
        next: () => {
          this.cargarCategorias(); // refrescar lista
          Swal.fire('Eliminada', 'La categoría fue eliminada exitosamente.', 'success');
        },
        error: (err) => {
          console.error('Error al eliminar categoría', err);
          Swal.fire('Error', 'No se pudo eliminar la categoría. Intenta nuevamente.', 'error');
        }
      });
    }
  });
}

}