-- Baza wiedzy — schemat (drzewo węzłów; każdy węzeł = instrukcja z treścią,
-- może mieć dzieci i własny opis). Adjacency list.

CREATE TABLE IF NOT EXISTS nodes (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  parent_id  INT NULL,
  slug       VARCHAR(190) NOT NULL,
  title      VARCHAR(255) NOT NULL,
  content    MEDIUMTEXT NULL,
  position   INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_nodes_parent FOREIGN KEY (parent_id) REFERENCES nodes(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_sibling (parent_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pełnotekstowe wyszukiwanie po tytule i treści (InnoDB FULLTEXT)
ALTER TABLE nodes ADD FULLTEXT KEY ft_title_content (title, content);
