 <!DOCTYPE html>
 <html lang="fr">

 <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title><?php echo isset($pageTitle) ? $pageTitle : 'Mon E-commerce'; ?></title>

     <!-- Votre CSS Tailwind compilé -->
     <link rel="stylesheet" href="/le-stock/css/style.css">

     <!-- Font Awesome -->
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
 </head>

 <body class="bg-gray-50 font-sans">
     <!-- Footer -->
     <footer class="footer">
         <div class="footer-content">
             <div class="footer-section">
                 <h4>LE-STOCK</h4>
                 <p style="color: #94a3b8; line-height: 1.6;">Pi bon platfòm pou achte ak vann pwodwi nan peyi a. Sekirize, rapid, efikas.</p>
             </div>
             <div class="footer-section">
                 <h4>Kategori</h4>
                 <?php foreach (array_slice($cats, 0, 5) as $c): ?>
                     <a href="categorie.php?id=<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></a>
                 <?php endforeach; ?>
             </div>
             <div class="footer-section">
                 <h4>Kontak</h4>
                 <a href="mailto:support@lestock.ht"><i class="fas fa-envelope"></i> support@lestock.ht</a>
                 <a href="tel:+50912345678"><i class="fas fa-phone"></i> +509 1234 5678</a>
                 <a href="#"><i class="fas fa-map-marker-alt"></i> Pòtoprens, Ayiti</a>
             </div>
             <div class="footer-section">
                 <h4>Suiv Nou</h4>
                 <div style="display: flex; gap: 1rem;">
                     <a href="#" style="font-size: 1.25rem;"><i class="fab fa-facebook"></i></a>
                     <a href="#" style="font-size: 1.25rem;"><i class="fab fa-instagram"></i></a>
                     <a href="#" style="font-size: 1.25rem;"><i class="fab fa-twitter"></i></a>
                     <a href="#" style="font-size: 1.25rem;"><i class="fab fa-whatsapp"></i></a>
                 </div>
             </div>
         </div>
         <div style="max-width: 1400px; margin: 2rem auto 0; padding-top: 2rem; border-top: 1px solid #1e293b; text-align: center; color: #64748b;">
             <p>&copy; <?= date('Y') ?> LE-STOCK. Tout dwa rezève.</p>
         </div>
     </footer>
 </body>