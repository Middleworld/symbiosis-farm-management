#!/usr/bin/env python3
"""
Conversation Logger for Future Fine-Tuning
Collects high-quality Q&A pairs for potential model training
"""

import json
import os
import datetime
from typing import Dict, Any

class ConversationLogger:
    """Log conversations for potential fine-tuning data"""
    
    def __init__(self, log_file: str = "farming_conversations.jsonl"):
        self.log_file = log_file
        
    def log_conversation(self, question: str, answer: str, context: Dict[str, Any] = None, quality_score: float = None):
        """Log a farming conversation for training data collection"""
        
        conversation = {
            "timestamp": datetime.datetime.now().isoformat(),
            "question": question,
            "answer": answer,
            "context": context or {},
            "quality_score": quality_score,
            "model_used": context.get("model", "unknown") if context else "unknown",
            "rag_entries_used": len(context.get("farm_context", "").split("KNOWLEDGE")) if context else 0,
            "character_count": len(answer),
            "has_farmos_data": bool(context.get("variety_data")) if context else False
        }
        
        # Append to JSONL file (each line is a JSON object)
        with open(self.log_file, "a", encoding="utf-8") as f:
            f.write(json.dumps(conversation) + "\n")
    
    def get_training_stats(self) -> Dict[str, Any]:
        """Get statistics about collected training data"""
        if not os.path.exists(self.log_file):
            return {"total_conversations": 0, "message": "No conversations logged yet"}
        
        conversations = []
        with open(self.log_file, "r", encoding="utf-8") as f:
            for line in f:
                try:
                    conversations.append(json.loads(line.strip()))
                except:
                    continue
        
        if not conversations:
            return {"total_conversations": 0}
        
        total = len(conversations)
        avg_length = sum(c["character_count"] for c in conversations) / total
        models_used = list(set(c["model_used"] for c in conversations))
        quality_scores = [c["quality_score"] for c in conversations if c["quality_score"] is not None]
        
        return {
            "total_conversations": total,
            "average_answer_length": int(avg_length),
            "models_used": models_used,
            "quality_scores": {
                "count": len(quality_scores),
                "average": sum(quality_scores) / len(quality_scores) if quality_scores else 0
            },
            "latest_conversation": conversations[-1]["timestamp"],
            "ready_for_training": total >= 100  # Need minimum dataset
        }
    
    def export_for_training(self, min_quality: float = 3.0, output_file: str = "training_data.jsonl"):
        """Export high-quality conversations for training"""
        if not os.path.exists(self.log_file):
            return {"error": "No conversations to export"}
        
        high_quality = []
        with open(self.log_file, "r", encoding="utf-8") as f:
            for line in f:
                try:
                    conv = json.loads(line.strip())
                    # Include if quality score is good or if no score but answer is substantial
                    if ((conv.get("quality_score", 0) >= min_quality) or 
                        (conv.get("quality_score") is None and conv["character_count"] > 200)):
                        
                        # Format for training
                        training_entry = {
                            "instruction": conv["question"],
                            "input": conv.get("context", {}).get("farm_context", ""),
                            "output": conv["answer"]
                        }
                        high_quality.append(training_entry)
                except:
                    continue
        
        # Save training data
        with open(output_file, "w", encoding="utf-8") as f:
            for entry in high_quality:
                f.write(json.dumps(entry) + "\n")
        
        return {
            "exported": len(high_quality),
            "output_file": output_file,
            "ready_for_training": len(high_quality) >= 50
        }

# Global logger instance
conversation_logger = ConversationLogger()

def log_farming_conversation(question: str, answer: str, context: Dict[str, Any] = None, quality_score: float = None):
    """Log a farming conversation"""
    conversation_logger.log_conversation(question, answer, context, quality_score)

def get_training_data_stats():
    """Get training data collection statistics"""
    return conversation_logger.get_training_stats()
