package net.hypergo.onchat.domain;

import com.vladmihalcea.hibernate.type.json.JsonType;
import org.hibernate.annotations.TypeDef;
import org.springframework.data.annotation.CreatedDate;
import org.springframework.data.annotation.LastModifiedDate;
import org.springframework.data.jpa.domain.support.AuditingEntityListener;

import javax.persistence.*;
import java.util.StringJoiner;

@MappedSuperclass
@EntityListeners(AuditingEntityListener.class)
@TypeDef(name = "JSON", typeClass = JsonType.class)
public class IdEntity {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    @Column(unique = true, nullable = false, columnDefinition = "BIGINT UNSIGNED")
    protected Long id;

    @CreatedDate
    @Column(nullable = false, columnDefinition = "BIGINT UNSIGNED")
    protected Long createTime;

    @LastModifiedDate
    @Column(nullable = false, columnDefinition = "BIGINT UNSIGNED")
    protected Long updateTime;

    public Long getId() {
        return id;
    }

    public void setId(Long id) {
        this.id = id;
    }

    public Long getCreateTime() {
        return createTime;
    }

    public void setCreateTime(Long createTime) {
        this.createTime = createTime;
    }

    public Long getUpdateTime() {
        return updateTime;
    }

    public void setUpdateTime(Long updateTime) {
        this.updateTime = updateTime;
    }

    @Override
    public String toString() {
        return new StringJoiner(", ", IdEntity.class.getSimpleName() + "[", "]")
                .add("id=" + id)
                .add("createTime=" + createTime)
                .add("updateTime=" + updateTime)
                .toString();
    }
}
