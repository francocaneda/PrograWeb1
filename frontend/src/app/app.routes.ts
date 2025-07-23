import { Routes } from '@angular/router';
import { AuthlayoutComponent } from './authlayout/authlayout.component';
import { MainLayoutComponent } from './main-layout/main-layout.component';
import { LoginComponent } from './login/login.component';
import { RegisUserComponent } from './regis-user/regis-user.component';
import { PasswordRecupComponent } from './password-recup/password-recup.component';
import { PasswordRecupPageComponent } from './password-recup-page/password-recup-page.component';
import { IndexForoComponent } from './index-foro/index-foro.component';
import { ProfileComponent } from './profile/profile.component';
import { CategoryComponent } from './category/category.component';
import { CreatePostComponent } from './create-post/create-post.component';
import { PostListComponent } from './post-list/post-list.component';
import { PostDetailComponent } from './post-detail/post-detail.component';
import { AdminUsuariosComponent } from './admin-usuarios/admin-usuarios.component'; // 👈 Agregado
import { AdminGuard } from './auth/admin.guard';
import { AdminPanelComponent } from './admin-panel/admin-panel.component';  // 👈 Agregado (si tenés uno)

export const routes: Routes = [
  {
    path: 'main-layout',
    component: MainLayoutComponent,
    children: [
      // Rutas hijas que usan el layout principal con navbar y footer
      { path: 'index', component: IndexForoComponent },
      { path: 'profile', component: ProfileComponent },
      { path: 'category', component: CategoryComponent },
      { path: 'create-post', component: CreatePostComponent },
      { path: 'categorias/:id', component: PostListComponent },
      { path: 'post/:id', component: PostDetailComponent },

      // 👉 Ruta para admin (con guard opcional)
      {
        path: 'admin-usuarios',
        component: AdminUsuariosComponent,
        canActivate: [AdminGuard] // 👈 Protege para que solo el admin pueda entrar
      },
      { path: 'admin-panel', component: AdminPanelComponent, canActivate: [AdminGuard] }
    ],
  },
  {
    path: '',
    component: AuthlayoutComponent, // Rutas que solo usan el footer
    children: [
      { path: 'loginscreen', component: LoginComponent },
      { path: '', redirectTo: 'loginscreen', pathMatch: 'full' },
      { path: 'registrarte', component: RegisUserComponent },
      { path: 'password-recup', component: PasswordRecupComponent },
      { path: 'passwordRecup-page', component: PasswordRecupPageComponent },
    ],
  },
];
