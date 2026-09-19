<script setup lang="ts">
const year = new Date().getFullYear()
</script>

<template>
  <footer class="site-footer">
    <div class="container footer-grid">
      <div>
        <h4>Quick Links</h4>
        <ul>
          <li><RouterLink to="/">Home</RouterLink></li>
          <li><RouterLink to="/browse">Browse</RouterLink></li>
          <li><RouterLink to="/docs">Docs</RouterLink></li>
        </ul>
      </div>
      <div>
        <h4>Contact</h4>
        <ul class="plain">
          <li>Ρωμανού 3, Χαλέπα, 73133 Χανιά</li>
          <li><a href="mailto:info@epictetuslibrary.org">info@epictetuslibrary.org</a></li>
          <li><a href="https://ee.hmu.gr" target="_blank" rel="noopener">ee.hmu.gr</a></li>
        </ul>
      </div>
      <div>
        <h4>Follow Us</h4>
        <div class="social-links">
          <a href="https://www.linkedin.com/company/epictetus-hmu" target="_blank" rel="noopener" aria-label="LinkedIn">LinkedIn</a>
          <a href="https://www.instagram.com/ee.epictetus_/" target="_blank" rel="noopener" aria-label="Instagram">Instagram</a>
          <a href="https://github.com/epictetushmu" target="_blank" rel="noopener" aria-label="GitHub">GitHub</a>
        </div>
      </div>
    </div>
    <div class="container footer-bottom">
      <small>&copy; {{ year }} Epictetus Library. All rights reserved.</small>
      <small class="text-muted">
        A project of
        <a href="https://ee.hmu.gr" target="_blank" rel="noopener">Department of Electrical &amp; Computer Engineering, HMU</a>
      </small>
    </div>
  </footer>
</template>

<style scoped>
.site-footer {
  background: var(--color-navy);
  color: #d8dde5;
  padding: 2.5rem 0 1.5rem;
  margin-top: 3rem;
}

.footer-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 2rem;
}

.site-footer h4 {
  color: var(--color-accent);
  font-size: 1rem;
}

.site-footer ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.site-footer ul.plain li {
  color: #d8dde5;
}

.site-footer a {
  color: #d8dde5;
}

.social-links {
  display: flex;
  gap: 1rem;
}

.footer-bottom {
  border-top: 1px solid rgba(255, 255, 255, 0.15);
  margin-top: 2rem;
  padding-top: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  text-align: center;
}
</style>
