import { Routes } from '@angular/router';
import { authGuard, guestGuard } from './core/auth.guard';

export const routes: Routes = [
  { path: '', pathMatch: 'full', redirectTo: 'documentos' },
  {
    path: 'login',
    canActivate: [guestGuard],
    loadComponent: () => import('./auth/login').then((m) => m.Login),
    title: 'Ingresar · Mini Firma',
  },
  {
    path: 'documentos',
    canActivate: [authGuard],
    children: [
      {
        path: '',
        loadComponent: () => import('./documents/document-list').then((m) => m.DocumentList),
        title: 'Documentos · Mini Firma',
      },
      {
        path: 'nuevo',
        loadComponent: () => import('./documents/document-new').then((m) => m.DocumentNew),
        title: 'Nuevo documento · Mini Firma',
      },
      {
        path: ':id',
        loadComponent: () => import('./documents/document-detail').then((m) => m.DocumentDetail),
        title: 'Documento · Mini Firma',
      },
    ],
  },
  {
    // Pública: el enlace único que recibe cada firmante.
    path: 'firmar/:token',
    loadComponent: () => import('./signing/sign-page').then((m) => m.SignPage),
    title: 'Firmar documento · Mini Firma',
  },
  { path: '**', redirectTo: 'documentos' },
];
